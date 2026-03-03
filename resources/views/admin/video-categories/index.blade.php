@extends('layouts.app')

@section('title', 'Video Categories')
@section('page-title', 'Video Categories')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.videos.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Back to Videos
            </a>
        </div>
        @can('create', App\Models\VideoCategory::class)
            <x-button variant="primary" :href="route('admin.video-categories.create')">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Category
            </x-button>
        @endcan
    </div>

    @if($categories->isEmpty())
        <x-card>
            <x-empty-state title="No categories" description="Create categories to organize your videos.">
                <x-slot:action>
                    @can('create', App\Models\VideoCategory::class)
                        <x-button variant="primary" size="sm" :href="route('admin.video-categories.create')">Create Category</x-button>
                    @endcan
                </x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <x-data-table :headers="['name' => 'Name', 'slug' => 'Slug', 'videos' => 'Videos', 'order' => 'Order', 'status' => 'Status', 'actions' => '']">
            @foreach($categories as $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $category->name }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ $category->slug }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ $category->videos_count }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ $category->sort_order }}</td>
                    <td class="px-6 py-3">
                        @if($category->is_active)
                            <x-badge type="success">Active</x-badge>
                        @else
                            <x-badge type="danger">Inactive</x-badge>
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            @can('update', $category)
                                <a href="{{ route('admin.video-categories.edit', $category) }}" class="text-sm text-primary-600 hover:text-primary-800">Edit</a>
                            @endcan
                            @can('delete', $category)
                                <form method="POST" action="{{ route('admin.video-categories.destroy', $category) }}"
                                      onsubmit="return confirm('Are you sure? Videos in this category will become uncategorized.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach

            <x-slot:pagination>
                {{ $categories->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
