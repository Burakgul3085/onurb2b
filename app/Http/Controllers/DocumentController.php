<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\DeliveryDocument;
use App\Policies\DeliveryDocumentPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function show(Request $request, DeliveryDocument $document): Response
    {
        $policy = app(DeliveryDocumentPolicy::class);
        abort_unless($policy->view($request->user(), $document), $policy->deniedStatus($request->user(), $document));
        $document->load('lines');

        $logo = null;
        $path = CompanySetting::current()->logo_path;
        $fullPath = is_string($path) && $path !== '' ? storage_path('app/public/'.$path) : null;
        $mime = is_string($fullPath) && is_file($fullPath) ? mime_content_type($fullPath) : false;

        if (is_string($mime) && str_starts_with($mime, 'image/') && is_string($fullPath)) {
            $logo = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($fullPath));
        }

        return Pdf::loadView('documents.delivery', [
            'document' => $document,
            'logo' => $logo,
            'disclaimer' => DeliveryDocument::DISCLAIMER,
        ])->setPaper('a4')->stream($document->number.'.pdf');
    }
}
