@extends('layouts.vertical', ['subtitle' => $item->exists ? 'Edit Item' : 'Add Item'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Stores', 'subtitle' => $item->exists ? 'Edit Item' : 'Add Item'])
    @include('admin.stores._styles')

    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="card st-card">
                <div class="card-body">
                    <h5 class="mb-3">{{ $item->exists ? 'Edit ' . $item->part_name : 'New store item' }}</h5>

                    @if ($errors->any())
                        <div class="alert alert-danger py-2">
                            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ $item->exists ? route('admin.stores.inventory.update', $item) : route('admin.stores.inventory.store') }}">
                        @csrf
                        @if ($item->exists) @method('PUT') @endif

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Item Code</label>
                                <input type="text" name="item_code" class="form-control" maxlength="50" value="{{ old('item_code', $item->item_code) }}" placeholder="{{ $nextCode ?? '' }}">
                                @if ($nextCode)<div class="form-text">Leave blank to use {{ $nextCode }}.</div>@endif
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Part Name <span class="text-danger">*</span></label>
                                <input type="text" name="part_name" class="form-control" maxlength="191" required value="{{ old('part_name', $item->part_name) }}" placeholder="e.g. Door roller 50mm">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">—</option>
                                    @foreach (\App\Models\InventoryItem::CATEGORIES as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $item->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" maxlength="100" value="{{ old('brand', $item->brand) }}" placeholder="e.g. Otis, Kone, Schindler">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Part Number</label>
                                <input type="text" name="part_number" class="form-control" maxlength="100" value="{{ old('part_number', $item->part_number) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select" required>
                                    @foreach (\App\Models\InventoryItem::UNITS as $u)
                                        <option value="{{ $u }}" {{ old('unit', $item->unit) === $u ? 'selected' : '' }}>{{ $u }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Unit Cost (QAR) <span class="text-danger">*</span></label>
                                <input type="number" name="unit_cost" class="form-control" min="0" step="0.01" required value="{{ old('unit_cost', $item->unit_cost ?? 0) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Minimum Stock</label>
                                <input type="number" name="min_stock" class="form-control" min="0" step="0.01" value="{{ old('min_stock', $item->min_stock ? qty($item->min_stock) : '') }}">
                                <div class="form-text">Alert when stock falls to this level.</div>
                            </div>
                            @unless ($item->exists)
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Opening Stock</label>
                                    <input type="number" name="opening_stock" class="form-control" min="0" step="0.01" value="{{ old('opening_stock') }}">
                                    <div class="form-text">Quantity in the store now.</div>
                                </div>
                            @else
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Current Stock</label>
                                    <input type="text" class="form-control" disabled value="{{ qty($item->stock_qty) }} {{ $item->unit }}">
                                    <div class="form-text">Change it with Receive / Correct stock.</div>
                                </div>
                            @endunless
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Store Location</label>
                                <input type="text" name="store_location" class="form-control" maxlength="150" value="{{ old('store_location', $item->store_location) }}" placeholder="e.g. Rack B-3">
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Active (can be requested)</label>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" maxlength="2000">{{ old('remarks', $item->remarks) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ $item->exists ? route('admin.stores.inventory.show', $item) : route('admin.stores.inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">{{ $item->exists ? 'Save changes' : 'Add item' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
