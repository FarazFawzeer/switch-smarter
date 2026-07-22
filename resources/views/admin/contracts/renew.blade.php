@extends('layouts.vertical', ['subtitle' => 'Renew Contract'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'Renew Contract'])

    <div id="message"></div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
            <p class="card-subtitle">Contract No: {{ $contract->contract_number }} — {{ $contract->location }}</p>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                This contract expired on <strong>{{ $contract->contract_end_date->format('d M Y') }}</strong>.
                Renewing it will keep the same contract number, elevator units, and team assignment — you'll just set a new contract period below.
            </div>

            <form id="renewForm" action="{{ route('admin.contracts.renew.store', $contract->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">New Contract Start Date</label>
                        <input type="date" name="contract_start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">New Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.contracts.show', $contract->id) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Renew Contract</button>
                </div>
            </form>
        </div>
    </div>

    @if($contract->renewals->count())
        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">Renewal History</h6></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Previous Term</th><th>Renewed To</th><th>Renewed By</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @foreach($contract->renewals as $renewal)
                            <tr>
                                <td>{{ $renewal->previous_start_date->format('d M Y') }} – {{ $renewal->previous_end_date->format('d M Y') }}</td>
                                <td>{{ $renewal->new_start_date->format('d M Y') }} – {{ $renewal->new_end_date->format('d M Y') }}</td>
                                <td>{{ optional($renewal->renewedBy)->name ?? '—' }}</td>
                                <td>{{ $renewal->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script>
        document.getElementById('renewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);

            fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    let messageBox = document.getElementById('message');
                    if (data.success) {
                        messageBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        setTimeout(() => { window.location.href = data.redirect; }, 1200);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
@endsection