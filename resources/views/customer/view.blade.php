@extends('layouts.vertical', ['subtitle' => 'Customer View'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Customer', 'subtitle' => 'View'])


    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Customer List</h5>
            <p class="card-subtitle">All customers in your system with details.</p>
        </div>

        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3 justify-content-end">
                <div class="col-md-3">
                    <label for="filterType" class="form-label">Type</label>
                    <select id="filterType" class="form-select">
                        <option value="">All</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-none" id="serviceFilterWrapper">
                    <label for="filterService" class="form-label">Service</label>
                    <select id="filterService" class="form-select">
                        <option value="">All</option>
                        @foreach ($services as $service)
                            <option value="{{ $service }}">{{ $service }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filterPortal" class="form-label">Portal</label>
                    <select id="filterPortal" class="form-select">
                        <option value="">All</option>
                        @foreach ($portals as $portal)
                            <option value="{{ $portal }}">{{ $portal }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive" id="customerTable">
                @include('customer.index-table') {{-- this will be returned also in AJAX --}}
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('filterType');
            const serviceWrapper = document.getElementById('serviceFilterWrapper');
            const serviceSelect = document.getElementById('filterService');
            const portalSelect = document.getElementById('filterPortal');

            function fetchFilteredData(url = null) {
                let type = typeSelect.value;
                let service = serviceSelect.value;
                let portal = portalSelect.value;

                url = url || "{{ route('admin.customers.index') }}";
                url += `?type=${type}&service=${service}&portal=${portal}`;

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(data => {
                        document.getElementById('customerTable').innerHTML = data;
                    });
            }

            // Filters
            typeSelect.addEventListener('change', function() {
                if (this.value.toLowerCase() === 'corporate') {
                    serviceWrapper.classList.remove('d-none');
                } else {
                    serviceWrapper.classList.add('d-none');
                    serviceSelect.value = '';
                }
                fetchFilteredData();
            });

            serviceSelect.addEventListener('change', function() {
                fetchFilteredData();
            });
            portalSelect.addEventListener('change', function() {
                fetchFilteredData();
            });

            // Pagination AJAX
            document.addEventListener('click', function(e) {
                if (e.target.closest('#customerTable .pagination a')) {
                    e.preventDefault();
                    let url = e.target.getAttribute('href');
                    fetchFilteredData(url);
                }

            });
        });


        document.querySelectorAll('.delete-customer').forEach(button => {
            button.addEventListener('click', function() {
                let customerId = this.dataset.id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ url('admin/customers') }}/" + customerId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('customer-' + customerId).remove();
                                    Swal.fire('Deleted!', data.message, 'success');
                                } else {
                                    Swal.fire('Error!', data.message || 'Something went wrong!',
                                        'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error!', 'Something went wrong!', 'error');
                            });
                    }
                });
            });
        });
    </script>
@endsection
