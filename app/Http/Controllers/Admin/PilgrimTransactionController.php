<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pilgrim;
use App\Models\PilgrimTransaction;
use App\Services\PaymentProofStore;
use App\Support\SiteProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PilgrimTransactionController extends Controller
{
    public function store(Request $request, Pilgrim $pilgrim, PaymentProofStore $proofStore)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(PilgrimTransaction::typesFor($pilgrim->departure?->isHaji() ?? false)))],
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $proofStore->store(
                $request->file('proof'),
                $pilgrim->full_name.'-'.$data['type']
            );
        }

        $transaction = $pilgrim->transactions()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'],
            'notes' => $data['notes'] ?? null,
            'proof_path' => $proofPath,
            'invoice_number' => PilgrimTransaction::generateInvoiceNumber(),
            'invoice_created_at' => now(),
            'created_by' => $request->user()?->id,
        ]);

        $pilgrim->refresh();
        $message = 'Transaksi dicatat. Invoice '.$transaction->invoice_number.' siap dicetak.';
        if ($pilgrim->hasOverpayment()) {
            $message .= ' Lebih bayar: '.$pilgrim->formattedOverpayment().'.';
        }

        return back()->with('ok', $message);
    }

    public function showInvoice(Pilgrim $pilgrim, PilgrimTransaction $transaction)
    {
        abort_unless($transaction->pilgrim_id === $pilgrim->id, 404);

        if (! $transaction->hasInvoice()) {
            $transaction->update([
                'invoice_number' => PilgrimTransaction::generateInvoiceNumber(),
                'invoice_created_at' => now(),
            ]);
            $transaction->refresh();
        }

        $transaction->load(['pilgrim.departure', 'author']);
        $site = SiteProfile::current();

        return view('admin.operations.pilgrims.invoice', [
            'pilgrim' => $pilgrim,
            'transaction' => $transaction,
            'site' => $site,
        ]);
    }

    public function refund(Request $request, Pilgrim $pilgrim, PilgrimTransaction $transaction)
    {
        abort_unless($transaction->pilgrim_id === $pilgrim->id, 404);

        DB::transaction(function () use ($request, $pilgrim, $transaction): void {
            $payment = PilgrimTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->isRefund()) {
                throw ValidationException::withMessages([
                    'refund' => 'Transaksi refund tidak dapat direfund kembali.',
                ]);
            }

            if ($payment->refundTransaction()->exists()) {
                throw ValidationException::withMessages([
                    'refund' => 'Transaksi ini sudah direfund.',
                ]);
            }

            $pilgrim->transactions()->create([
                'refunded_transaction_id' => $payment->id,
                'type' => PilgrimTransaction::TYPE_REFUND,
                'amount' => $payment->amount,
                'paid_at' => now()->toDateString(),
                'notes' => 'Refund transaksi '.$payment->invoiceLabel(),
                'invoice_number' => PilgrimTransaction::generateInvoiceNumber(),
                'invoice_created_at' => now(),
                'created_by' => $request->user()?->id,
            ]);
        });

        return back()->with('ok', 'Refund berhasil dicatat sebagai transaksi baru.');
    }
}
