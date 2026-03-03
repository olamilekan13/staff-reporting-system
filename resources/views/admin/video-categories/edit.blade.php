@extends('layouts.app')

@section('title', 'Edit Video Category')
@section('page-title', 'Edit Video Category')

@section('content')
    <a href="{{ route('admin.video-categories.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Categories
    </a>

    <x-card title="Edit Category">
        <form method="POST" action="{{ route('admin.video-categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <x-input name="name" label="Name" :value="old('name', $category->name)" :error="$errors->first('name')" required />

                <div>
                    <label for="description" class="label">Description <span class="text-xs text-gray-400">(optional)</span></label>
                    <textarea name="description" id="description" rows="2"
                        class="input @error('description') border-red-300 @enderror">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-input name="sort_order" label="Sort Order" type="number" :value="old('sort_order', $category->sort_order)" :error="$errors->first('sort_order')" />

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                <x-button type="submit" variant="primary">Update Category</x-button>
                <a href="{{ route('admin.video-categories.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
