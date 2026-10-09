<?php

namespace App\Http\Controllers\Governance;

use App\Domains\HR\Models\Employee;
use App\Domains\Security\Models\DocumentAcknowledgment;
use App\Domains\Security\Models\InternalDocument;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function acknowledge(Request $request, string $id)
    {
        $document = InternalDocument::findOrFail($id);
        $user = $request->user();
        $employee = $user->employee
            ?? Employee::where('user_id', $user->id)->first()
            ?? Employee::where('email', $user->email)->first();

        if (! $employee) {
            abort(404, 'Profil karyawan tidak ditemukan.');
        }

        $ack = DocumentAcknowledgment::updateOrCreate(
            [
                'internal_document_id' => $document->id,
                'employee_id' => $employee->id,
            ],
            [
                'acknowledged_at' => now(),
                'ip_address' => $request->ip(),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfirmasi pembacaan dokumen berhasil dicatat.',
                'data' => $ack,
            ]);
        }

        return redirect()->back()->with('success', 'Konfirmasi pembacaan dokumen berhasil dicatat.');
    }
}
