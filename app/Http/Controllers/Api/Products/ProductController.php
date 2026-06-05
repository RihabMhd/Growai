<?php

namespace App\Http\Controllers\Api\Products;

use App\Application\Products\CreateProduct\CreateProductCommand;
use App\Application\Products\CreateProduct\CreateProductHandler;
use App\Application\Products\DeleteProduct\DeleteProductCommand;
use App\Application\Products\DeleteProduct\DeleteProductHandler;
use App\Application\Products\ListProducts\ListProductsQuery;
use App\Application\Products\UpdateProduct\UpdateProductCommand;
use App\Application\Products\UpdateProduct\UpdateProductHandler;
use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ProductData;
use App\Domain\Products\DTOs\ProductFilterData;
use App\Domain\Shopify\Models\Shop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\BulkDeleteRequest;
use App\Http\Requests\Products\BulkUpdateStatusRequest;
use App\Http\Requests\Products\CreateProductRequest;
use App\Http\Requests\Products\ListProductsRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
        private readonly CreateProductHandler       $createHandler,
        private readonly UpdateProductHandler       $updateHandler,
        private readonly DeleteProductHandler       $deleteHandler,
    ) {}

    // -------------------------------------------------------------------------
    // GET /api/shops/{shop}/products
    // -------------------------------------------------------------------------

    public function index(ListProductsRequest $request, Shop $shop): JsonResponse
    {
        $filters   = ProductFilterData::fromArray($request->validated());
        $paginator = $this->repository->findAllByShop($shop->id, $filters);

        return response()->json($paginator);
    }

    // -------------------------------------------------------------------------
    // GET /api/shops/{shop}/products/summary
    // -------------------------------------------------------------------------

    public function summary(Shop $shop): JsonResponse
    {
        $summary = $this->repository->getSummaryByShop($shop->id);

        return response()->json($summary->toArray());
    }

    // -------------------------------------------------------------------------
    // GET /api/shops/{shop}/products/{product}
    // -------------------------------------------------------------------------

    public function show(Shop $shop, int $productId): JsonResponse
    {
        $product = $this->repository->findByIdAndShop($productId, $shop->id);

        return response()->json($product);
    }

    // -------------------------------------------------------------------------
    // POST /api/shops/{shop}/products
    // -------------------------------------------------------------------------

    public function store(CreateProductRequest $request, Shop $shop): JsonResponse
    {
        $data = ProductData::fromArray(
            array_merge($request->validated(), ['shop_id' => $shop->id])
        );

        $product = $this->createHandler->handle(new CreateProductCommand($data));

        return response()->json($product, 201);
    }

    // -------------------------------------------------------------------------
    // PUT /api/shops/{shop}/products/{product}
    // -------------------------------------------------------------------------

    public function update(UpdateProductRequest $request, Shop $shop, int $productId): JsonResponse
    {
        $data = ProductData::fromArray(
            array_merge($request->validated(), ['shop_id' => $shop->id])
        );

        $product = $this->updateHandler->handle(
            new UpdateProductCommand(productId: $productId, shopId: $shop->id, data: $data)
        );

        return response()->json($product);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/shops/{shop}/products/{product}
    // -------------------------------------------------------------------------

    public function destroy(Shop $shop, int $productId): JsonResponse
    {
        $this->deleteHandler->handle(
            new DeleteProductCommand(productId: $productId, shopId: $shop->id)
        );

        return response()->json(null, 204);
    }

    // -------------------------------------------------------------------------
    // POST /api/shops/{shop}/products/bulk-delete
    // -------------------------------------------------------------------------

    public function bulkDestroy(BulkDeleteRequest $request, Shop $shop): JsonResponse
    {
        $deleted = $this->repository->bulkDeleteByShop(
            ids:    $request->validated('ids'),
            shopId: $shop->id,
        );

        return response()->json(['deleted' => $deleted]);
    }

    // -------------------------------------------------------------------------
    // POST /api/shops/{shop}/products/bulk-status
    // -------------------------------------------------------------------------

    public function bulkUpdateStatus(BulkUpdateStatusRequest $request, Shop $shop): JsonResponse
    {
        $updated = $this->repository->bulkUpdateStatusByShop(
            ids:    $request->validated('ids'),
            status: $request->validated('status'),
            shopId: $shop->id,
        );

        return response()->json(['updated' => $updated]);
    }
}