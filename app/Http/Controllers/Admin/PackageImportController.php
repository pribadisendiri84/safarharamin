<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PackageCsvImporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PackageImportController extends Controller
{
    public function create()
    {
        return view('admin.packages.import');
    }

    public function store(Request $request, PackageCsvImporter $importer)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $importer->import($request->file('csv'));

        $message = $result['created'].' paket draft dibuat.';
        if ($result['errors'] !== []) {
            $message .= ' '.count($result['errors']).' baris gagal.';
        }

        return redirect()
            ->route('admin.packages.index', ['data_complete' => 0])
            ->with('ok', $message)
            ->with('import_errors', $result['errors']);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print (PackageCsvImporter::templateCsv()),
            'contoh-import-paket.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }
}
