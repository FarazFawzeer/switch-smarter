@extends('layouts.vertical', ['subtitle' => 'Admin View'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Admin', 'subtitle' => 'View'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Admin List</h5>
            <p class="card-subtitle">All team members in your system with details.</p>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Reports To</th>
                            <th scope="col">Updated At</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr id="user-{{ $user->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
<img src="{{ storage_asset($user->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                            alt="{{ $user->name }}" class="avatar-sm rounded-circle">
                                        <div>
                                            <h6 class="mb-0">{{ $user->name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->type }}</td>
                                <td>
                                    @if($user->type === 'Supervisor')
                                        {{ optional($user->engineer)->name ?? '—' }}
                                    @elseif($user->type === 'Technician')
                                        {{ optional($user->supervisor)->name ?? '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $user->updated_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm w-100 delete-user"
                                        data-id="{{ $user->id }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="d-flex justify-content-end mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function() {
                let userId = this.dataset.id;

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
                        fetch("{{ url('admin/users') }}/" + userId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('user-' + userId).remove();
                                    Swal.fire('Deleted!', data.message, 'success');
                                } else {
                                    Swal.fire('Error!', data.message || 'Something went wrong!', 'error');
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