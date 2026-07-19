@extends('layouts.vertical', ['subtitle' => 'Customer Create'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Customer', 'subtitle' => 'Create'])


    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New Customer</h5>
        </div>

        <div class="card-body">
            <div id="message"></div> {{-- Success / Error messages --}}

            <form id="createCustomerForm" action="{{ route('admin.customers.store') }}" method="POST">

                @csrf

                {{-- Full Name + Email --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}"
                            placeholder="Ex: John Doe" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}"
                            placeholder="Ex: john@example.com" required>
                    </div>
                </div>

                {{-- Phone + Other Phone --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contact" class="form-label">Phone</label>
                        <input type="text" name="contact" id="contact" class="form-control"
                            value="{{ old('contact') }}" placeholder="Ex: +94771234567">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="other_phone" class="form-label">Other Phone</label>
                        <input type="text" name="other_phone" id="other_phone" class="form-control"
                            value="{{ old('other_phone') }}" placeholder="Ex: +94779876543">
                    </div>
                </div>

                {{-- WhatsApp + Date of Birth --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="whatsapp_number" class="form-label">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" id="whatsapp_number" class="form-control"
                            value="{{ old('whatsapp_number') }}" placeholder="Ex: +94771234567">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
                            value="{{ old('date_of_birth') }}">
                    </div>
                </div>


                {{-- Address + Country --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" name="address" id="address" class="form-control"
                            value="{{ old('address') }}" placeholder="Ex: 123 Main Street, Colombo">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" name="country" id="country" class="form-control"
                            value="{{ old('country') }}" placeholder="Ex: Sri Lanka">
                    </div>
                </div>

                {{-- Service + Heard Us --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="service" class="form-label">Service</label>
                        <select name="service" id="service" class="form-select">
                            <option value="">Select Service</option>
                            <option value="Tour Package" {{ old('service') == 'Tour Package' ? 'selected' : '' }}>Tour
                                Package</option>
                            <option value="Rent Vehicle" {{ old('service') == 'Rent Vehicle' ? 'selected' : '' }}>Rent
                                Vehicle</option>
                            <option value="Transportation" {{ old('service') == 'Transportation' ? 'selected' : '' }}>
                                Transportation</option>
                            <option value="Airline Ticketing"
                                {{ old('service') == 'Airline Ticketing' ? 'selected' : '' }}>Airline Ticketing</option>
                            <option value="Insurance Service"
                                {{ old('service') == 'Insurance Service' ? 'selected' : '' }}>Insurance Service</option>
                            <option value="Visa Assistance" {{ old('service') == 'Visa Assistance' ? 'selected' : '' }}>
                                Visa Assistance</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="heard_us" class="form-label">From where did you hear about us?</label>
                        <select name="heard_us" id="heard_us" class="form-select">
                            <option value="">Select</option>
                            <option value="Working Customer"
                                {{ old('heard_us') == 'Working Customer' ? 'selected' : '' }}>Working Customer</option>
                            <option value="Trip Advisor" {{ old('heard_us') == 'Trip Advisor' ? 'selected' : '' }}>Trip
                                Advisor</option>
                            <option value="Google" {{ old('heard_us') == 'Google' ? 'selected' : '' }}>Google</option>
                            <option value="Facebook" {{ old('heard_us') == 'Facebook' ? 'selected' : '' }}>Facebook
                            </option>
                            <option value="Instagram" {{ old('heard_us') == 'Instagram' ? 'selected' : '' }}>Instagram
                            </option>
                            <option value="TikTok" {{ old('heard_us') == 'TikTok' ? 'selected' : '' }}>TikTok</option>
                        </select>
                    </div>
                </div>

                {{-- Type + Company (conditional) --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="Individual" {{ old('type') == 'Individual' ? 'selected' : '' }}>Individual
                            </option>
                            <option value="Corporate" {{ old('type') == 'Corporate' ? 'selected' : '' }}>Corporate
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="companyDiv" style="display: none;">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" name="company_name" id="company_name" class="form-control"
                            value="{{ old('company_name') }}" placeholder="Ex: ABC Travels Pvt Ltd">
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Customer</button>
                </div>
            </form>

        </div>
    </div>

    <script>
        // Show company field if type = Corporate
        document.getElementById('type').addEventListener('change', function() {
            const companyDiv = document.getElementById('companyDiv');
            if (this.value === 'Corporate') {
                companyDiv.style.display = 'block';
            } else {
                companyDiv.style.display = 'none';
            }
        });
    </script>
    {{-- AJAX Submit --}}
    <script>
        document.getElementById('createCustomerForm').addEventListener('submit', function(e) {
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
                        form.reset();
                        setTimeout(() => {
                            messageBox.innerHTML = "";
                        }, 3000);
                    } else {
                        let errors = data.errors ? data.errors.join('<br>') : data.message;
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML =
                        `<div class="alert alert-danger">Something went wrong. Please try again.</div>`;
                    console.error(error);
                });
        });
    </script>
@endsection
