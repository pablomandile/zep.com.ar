<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateShortLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Http\Resources\LinkResource;
use App\Models\Link;
use App\Services\LinkStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class LinkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search'));
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $links = $request->user()->links()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('original_url', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return LinkResource::collection($links);
    }

    public function store(StoreLinkRequest $request, CreateShortLink $create): JsonResponse
    {
        [$link, $created] = $create->handle($request->user(), $request->validated());

        return (new LinkResource($link))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }

    public function show(Request $request, string $code): LinkResource
    {
        return new LinkResource($this->find($request, $code));
    }

    public function update(UpdateLinkRequest $request, string $code): LinkResource
    {
        $link = $this->find($request, $code);
        $link->update($request->validated());

        return new LinkResource($link);
    }

    public function destroy(Request $request, string $code): Response
    {
        $this->find($request, $code)->delete();

        return response()->noContent();
    }

    public function stats(Request $request, string $code, LinkStats $stats): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $link = $this->find($request, $code);

        return response()->json(['data' => [
            'code' => $link->code,
            'short_url' => $link->short_url,
            ...$stats->for(
                $link,
                isset($data['from']) ? Carbon::parse($data['from']) : null,
                isset($data['to']) ? Carbon::parse($data['to']) : null,
            ),
        ]]);
    }

    /**
     * Solo los links propios: uno ajeno responde 404, igual que uno inexistente.
     */
    private function find(Request $request, string $code): Link
    {
        return $request->user()->links()->where('code', $code)->firstOrFail();
    }
}
