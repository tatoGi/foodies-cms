<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateBlockContentRequest;
use App\Http\Requests\Admin\GenerateSeoRequest;
use App\Http\Requests\Admin\TranslateLocalizedContentRequest;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Services\AdminContentAiService;
use App\Services\AiBlockContentService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class AdminAiController extends Controller
{
    public function __construct(
        private readonly AdminContentAiService $contentAiService,
        private readonly AiBlockContentService $blockContentService,
    ) {}

    public function generateBlock(GenerateBlockContentRequest $request): JsonResponse
    {
        try {
            $data = $this->blockContentService->generate(
                (string) $request->validated('scope'),
                (string) $request->validated('block_type'),
                (string) $request->validated('locale'),
                (array) $request->validated('context', []),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => __('AI generation is unavailable right now. Please try again.')], 503);
        }

        if ($data === []) {
            return response()->json(['message' => __('No content could be generated for this block.')], 422);
        }

        return response()->json(['data' => $data]);
    }

    public function translatePage(TranslateLocalizedContentRequest $request, Page $page): JsonResponse
    {
        return response()->json(
            $this->contentAiService->translatePage(
                $page,
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function translatePageDraft(TranslateLocalizedContentRequest $request): JsonResponse
    {
        return response()->json(
            $this->contentAiService->translatePageDraft(
                (array) $request->input('translations', []),
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function generatePageSeo(GenerateSeoRequest $request, Page $page): JsonResponse
    {
        return response()->json(
            $this->contentAiService->generatePageSeo(
                $page,
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function generatePageDraftSeo(GenerateSeoRequest $request): JsonResponse
    {
        return response()->json(
            $this->contentAiService->generatePageDraftSeo(
                (array) $request->input('translations', []),
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function translatePost(TranslateLocalizedContentRequest $request, Post $post): JsonResponse
    {
        return response()->json(
            $this->contentAiService->translatePost(
                $post,
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function translatePostDraft(TranslateLocalizedContentRequest $request): JsonResponse
    {
        return response()->json(
            $this->contentAiService->translatePostDraft(
                (array) $request->input('translations', []),
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function generatePostSeo(GenerateSeoRequest $request, Post $post): JsonResponse
    {
        return response()->json(
            $this->contentAiService->generatePostSeo(
                $post,
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function generatePostDraftSeo(GenerateSeoRequest $request): JsonResponse
    {
        return response()->json(
            $this->contentAiService->generatePostDraftSeo(
                (array) $request->input('translations', []),
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function translateProduct(TranslateLocalizedContentRequest $request, Product $product): JsonResponse
    {
        return response()->json(
            $this->contentAiService->translateProduct(
                $product,
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function generateProductSeo(GenerateSeoRequest $request, Product $product): JsonResponse
    {
        return response()->json(
            $this->contentAiService->generateProductSeo(
                $product,
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function translateProductDraft(TranslateLocalizedContentRequest $request): JsonResponse
    {
        return response()->json(
            $this->contentAiService->translateProductDraft(
                (array) $request->input('translations', []),
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }

    public function generateProductDraftSeo(GenerateSeoRequest $request): JsonResponse
    {
        return response()->json(
            $this->contentAiService->generateProductDraftSeo(
                (array) $request->input('translations', []),
                (string) $request->validated('target_locale'),
                $request->validated('source_locale')
            )
        );
    }
}
