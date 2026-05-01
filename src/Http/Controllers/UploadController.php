<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Http\Controllers;

use Dskripchenko\LaravelAdminMedia\Services\MediaService;
use Dskripchenko\LaravelApi\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/admin/media/upload
 *
 * Form-data:
 *   - file (required, single file)
 *   - collection (string, default 'default')
 *   - responsive_set (string, optional — генерирует variants по
 *     `admin-media.responsive_sets.{set}` синхронно)
 *   - alt / title / description / tags[] (optional)
 */
final class UploadController extends ApiController
{
    public function __construct(private readonly MediaService $service) {}

    /**
     * @input file $file
     * @input string ?$collection
     * @input string ?$responsive_set
     *
     * @output object $payload
     * @output object $payload.media
     *
     * @security AdminSession
     *
     * @response 200 {SuccessResponse}
     * @response 422 {ValidationErrorResponse}
     */
    public function upload(Request $request): JsonResponse
    {
        $maxMb = (int) config('admin-media.max_size_mb', 50);
        $allowed = (array) config('admin-media.allowed_mimes', []);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.($maxMb * 1024)],
            'collection' => ['nullable', 'string', 'max:64'],
            'responsive_set' => ['nullable', 'string', 'max:64'],
            'alt' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $file = $request->file('file');
        if ($file === null) {
            return $this->error([
                'errorKey' => 'file_missing',
                'message' => 'File is required',
            ], 422);
        }

        if ($allowed !== [] && ! in_array($file->getMimeType(), $allowed, true)) {
            return $this->error([
                'errorKey' => 'unsupported_mime',
                'message' => 'MIME type '.$file->getMimeType().' is not allowed',
            ], 422);
        }

        $extra = array_filter([
            'alt' => $data['alt'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'tags' => $data['tags'] ?? null,
            'uploader_id' => $request->user()?->getKey(),
        ], fn ($v) => $v !== null);

        $media = $this->service->upload(
            $file,
            $data['collection'] ?? null,
            $data['responsive_set'] ?? null,
            $extra,
        );

        return $this->success(['media' => $media->loadMissing('variants')->toArray()]);
    }
}
