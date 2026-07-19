<table class="table table-hover table-centered">
    <thead class="table-light">
        <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Other Phone</th>
            <th>WhatsApp</th>
            <th>DOB</th>
            <th>Type</th>
            <th>Company</th>
            <th>Address</th>
            <th>Country</th>
            <th>Service</th>
            <th>Heard Us</th>
            <th>DOE</th>
            <th>Portal</th>
            <th>Updated</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($customers as $customer)
            <tr id="customer-{{ $customer->id }}">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ asset('/images/users/avatar-6.jpg') }}" alt="{{ $customer->name }}"
                            class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                        <span>{{ $customer->name }}</span>
                    </div>
                </td>
                <td>{{ $customer->customer_code ?? '-' }}</td>
                <td>{{ $customer->email ?? '-' }}</td>
                <td>{{ $customer->contact ?? '-' }}</td>
                <td>{{ $customer->other_phone ?? '-' }}</td>
                <td>{{ $customer->whatsapp_number ?? '-' }}</td>
                <td>{{ $customer->date_of_birth ? \Carbon\Carbon::parse($customer->date_of_birth)->format('d M Y') : '-' }}
                </td>
                <td>{{ ucfirst($customer->type) }}</td>
                <td>{{ $customer->company_name ?? '-' }}</td>
                <td>{{ $customer->address }}</td>
                <td>{{ $customer->country ?? '-' }}</td>
                <td>{{ $customer->service ?? '-' }}</td>
                <td>{{ $customer->heard_us ?? '-' }}</td>
                <td>{{ $customer->date_of_entry ? \Carbon\Carbon::parse($customer->date_of_entry)->format('d M Y, h:i A') : '-' }}
                </td>
                <td>{{ $customer->portal ?? '-' }}</td>
                <td>{{ $customer->updated_at->format('d M Y, h:i A') }}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm delete-customer" data-id="{{ $customer->id }}">
                        Delete
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="16" class="text-center text-muted">No customers found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Pagination -->
<div class="d-flex justify-content-end mt-3">
    {{ $customers->links() }}
</div>
