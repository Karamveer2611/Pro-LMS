<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\Slug;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Category::class);

        return CategoryResource::collection(Category::orderBy('name')->get());
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $this->authorize('create', Category::class);

        $data = $request->validated();
        $category = Category::create([
            ...$data,
            'slug' => Slug::unique('categories', $data['name']),
        ]);

        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', Category::class);

        $data = $request->validated();
        if (isset($data['name'])) {
            $data['slug'] = Slug::unique('categories', $data['name'], $category->id);
        }
        $category->update($data);

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', Category::class);

        $category->delete();

        return response()->json(status: 204);
    }
}
