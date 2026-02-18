<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PTK;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ApprovalController extends Controller
{
    /**
     * Approve PTK (multistage):
     * - Stage 1: Kabag/Manager  -> status: Waiting Director
     * - Stage 2: Direktur       -> status: Completed
     * Catatan: tidak ada penomoran otomatis di tahap mana pun.
     */
    public function approve(PTK $ptk): RedirectResponse
    {
        $u = auth()->user();

        // Stage 1 → Waiting Director
        if ($u->hasAnyRole(['kabag_qc', 'manager_hr', 'kabag_mtc'])) {
            abort_unless(
                $ptk->status === PTK::STATUS_SUBMITTED && is_null($ptk->approved_stage1_at),
                403,
                'PTK tidak dalam antrian Stage 1.'
            );

            $ptk->update([
                'approved_stage1_by' => $u->id,
                'approved_stage1_at' => now(),
                'status' => PTK::STATUS_WAITING_DIRECTOR,
            ]);

            // audit (lewati jika helper tidak tersedia)
            if (function_exists('\activity')) {
                \activity()
                    ->performedOn($ptk)
                    ->causedBy($u)
                    ->withProperties(['stage' => 'stage1'])
                    ->log('PTK approved by Kabag/Manager');
            }

            return back()->with('ok', 'Disetujui Kabag/Manager. Menunggu Direktur.');
        }

        // Stage 2 → Completed
        if ($u->hasRole('director')) {
            abort_unless(
                $ptk->status === PTK::STATUS_WAITING_DIRECTOR && is_null($ptk->approved_stage2_at),
                403,
                'PTK tidak dalam antrian Direktur.'
            );

            $ptk->update([
                'approved_stage2_by' => $u->id,
                'approved_stage2_at' => now(),
                'status' => PTK::STATUS_COMPLETED,
            ]);

            if (function_exists('\activity')) {
                \activity()
                    ->performedOn($ptk)
                    ->causedBy($u)
                    ->withProperties(['stage' => 'stage2'])
                    ->log('PTK approved by Director');
            }

            return back()->with('ok', 'Disetujui Direktur. PTK Completed.');
        }

        abort(403, 'User tidak berwenang approve.');
    }

    /**
     * Reject PTK (multistage):
     * - Stage 1 reject oleh Kabag/Manager
     * - Stage 2 reject oleh Direktur
     * - Kembali ke status "In Progress" untuk revisi admin.
     */
    public function reject(Request $request, PTK $ptk): RedirectResponse
    {
        $u = auth()->user();

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        // Stage 1 reject oleh Kabag/Manager
        if ($u->hasAnyRole(['kabag_qc', 'manager_hr', 'kabag_mtc'])) {
            abort_unless(
                $ptk->status === PTK::STATUS_SUBMITTED || $ptk->status === PTK::STATUS_WAITING_DIRECTOR,
                403,
                'PTK tidak bisa direject pada tahap ini.'
            );

            $ptk->update([
                'last_reject_stage' => 'stage1',
                'last_reject_reason' => $data['reason'],
                'last_reject_by' => $u->id,
                'last_reject_at' => now(),
                'status' => PTK::STATUS_IN_PROGRESS, // kembali ke admin
            ]);

            if (function_exists('\activity')) {
                \activity()
                    ->performedOn($ptk)
                    ->causedBy($u)
                    ->withProperties(['stage' => 'stage1', 'reason' => $data['reason']])
                    ->log('PTK rejected by Kabag/Manager');
            }

            return back()->with('ok', 'Ditolak & dikembalikan ke Admin (In Progress).');
        }

        // Stage 2 reject oleh Direktur
        if ($u->hasRole('director')) {
            abort_unless(
                $ptk->status === PTK::STATUS_WAITING_DIRECTOR && is_null($ptk->approved_stage2_at),
                403,
                'PTK tidak dalam antrian Direktur.'
            );

            $ptk->update([
                'last_reject_stage' => 'stage2',
                'last_reject_reason' => $data['reason'],
                'last_reject_by' => $u->id,
                'last_reject_at' => now(),
                'status' => PTK::STATUS_IN_PROGRESS,
            ]);

            if (function_exists('\activity')) {
                \activity()
                    ->performedOn($ptk)
                    ->causedBy($u)
                    ->withProperties(['stage' => 'stage2', 'reason' => $data['reason']])
                    ->log('PTK rejected by Director');
            }

            return back()->with('ok', 'Ditolak Direktur & dikembalikan ke Admin (In Progress).');
        }

        abort(403, 'User tidak berwenang reject.');
    }
}
