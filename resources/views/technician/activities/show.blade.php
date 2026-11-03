@extends('layouts.technician', ['subtitle' => 'Activity'])

@section('content')
    @php
        $colorMap = ['ppm' => '#2E9E5B', 'casual_job' => '#9B59B6', 'repair' => '#F0730A', 'breakdown' => '#D64545'];
        $labelMap = ['ppm' => 'Preventive Maintenance', 'casual_job' => 'Casual Job', 'repair' => 'Repair', 'breakdown' => 'Breakdown'];
        $color = $colorMap[$job->job_type];
        $label = $labelMap[$job->job_type];
    @endphp

    <style>
        .act-header { background: {{ $color }}; color: #fff; padding: 14px 16px; border-radius: 14px 14px 0 0; display:flex; justify-content:space-between; align-items:center; }
        .act-header a { color: #fff; font-size: 20px; }
        .act-body { background: #fff; border-radius: 0 0 14px 14px; padding: 16px; box-shadow: 0 1px 3px rgba(15,42,67,0.08); margin-bottom: 90px; }
        .act-field-label { font-size: 12px; font-weight: 600; color: #56606f; margin-bottom: 4px; }
        .act-info-value { font-weight: 600; color: #16233b; }
        .parts-row { display:flex; gap:8px; margin-bottom:8px; }
        .attach-row { display: flex; gap: 8px; }
        .attach-btn { flex:1; text-align:center; padding: 14px 8px; border: 1px dashed #cfd4db; border-radius: 10px; color:#56606f; cursor:pointer; background:#fafbfd; }
        .attach-btn .ui-icon { display:block; width: 22px; height: 22px; margin-bottom:4px; }
        #sigPad { border: 1px solid #e7e9ee; border-radius: 8px; width: 100%; touch-action: none; background:#fff; }
        .act-footer-btns { position: fixed; bottom: 66px; left: 0; right: 0; background:#fff; padding: 10px 16px; box-shadow: 0 -2px 10px rgba(15,42,67,0.08); display:flex; gap:8px; z-index: 900; }
        .start-photo-btn {
            display: block; text-align: center; padding: 40px 16px; border: 2px dashed {{ $color }}; border-radius: 14px;
            color: {{ $color }}; cursor: pointer; background: {{ $color }}0D;
        }
        .start-photo-btn .ui-icon { width: 40px; height: 40px; display: block; margin-bottom: 8px; }
        .location-box {
            background: #F5F7FA; border-radius: 10px; padding: 10px 12px; font-size: 12px; color: #56606f;
            display: flex; align-items: flex-start; gap: 6px; margin-bottom: 12px;
        }
        .location-box .ui-icon { width: 15px; height: 15px; color: {{ $color }}; flex-shrink: 0; margin-top: 1px; }
        .readonly-field { background: #F5F7FA !important; color: #56606f; }
        .status-choice { display: flex; gap: 8px; margin-bottom: 12px; }
        .status-choice label {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px 8px; border-radius: 10px; border: 2px solid #e7e9ee;
            font-weight: 700; font-size: 14px; color: #56606f; cursor: pointer; background: #fff;
        }
        .status-choice input { display: none; }
        .status-choice label:has(input[value="working"]:checked) { border-color: #2E9E5B; background: rgba(46,158,91,0.08); color: #2E9E5B; }
        .status-choice label:has(input[value="shutdown"]:checked) { border-color: #D64545; background: rgba(214,69,69,0.08); color: #D64545; }
        .shutdown-banner { background: rgba(214,69,69,0.10); color: #B23030; border-radius: 10px; padding: 10px 12px; font-size: 13px; font-weight: 600; margin-bottom: 12px; display: flex; gap: 6px; }
        .team-line { background: #F5F7FA; border-radius: 10px; padding: 8px 12px; font-size: 12.5px; color: #56606f; margin-bottom: 12px; }
        .backoffice-note { font-size: 11px; color: #8792a2; display: flex; align-items: center; gap: 4px; margin-top: -8px; margin-bottom: 12px; }
    </style>

    <div class="act-header">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('technician.jobs.index') }}"><x-ui-icon name="arrow-left" /></a>
            <span class="fw-bold">{{ $label }}</span>
        </div>
        @if($job->status === 'on_hold')
            <span class="badge bg-light text-dark">On Hold</span>
        @elseif($job->status === 'completed')
            <span class="badge bg-light text-dark">Completed</span>
        @endif
    </div>

    <div class="act-body">
        <div id="message"></div>

        @if(optional($job->unit)->isShutdown())
            <div class="shutdown-banner">
                <x-ui-icon name="alert" size="16" />
                <span>This elevator is currently SHUT DOWN{{ $job->unit->shutdown_since ? ' since ' . $job->unit->shutdown_since->format('d M Y') : '' }}{{ $job->unit->shutdown_reason ? ' — ' . $job->unit->shutdown_reason : '' }}</span>
            </div>
        @endif

        @if(isset($team) && $team->count() > 1)
            <div class="team-line">
                <x-ui-icon name="users" />
                <strong>Team on this job:</strong> {{ $team->implode(', ') }}
                <div class="small text-muted">Counted as one job — each technician's own times go to their own timesheet.</div>
            </div>
        @endif

        <p class="act-field-label mb-1">Date &amp; Time (Auto-captured)</p>
        <p class="act-info-value mb-3">{{ $job->check_in_time?->format('d M Y | h:i A') ?? 'Not started yet' }}</p>

        @if($job->check_in_time && $job->check_in_lat)
            <div class="location-box" id="startLocationBox" data-lat="{{ $job->check_in_lat }}" data-lng="{{ $job->check_in_lng }}">
                <x-ui-icon name="map-pin" />
                <span>Started from: <span id="startLocationText">Loading address...</span></span>
            </div>
        @endif

        @if($job->check_out_time && $job->check_out_lat)
            <div class="location-box" id="endLocationBox" data-lat="{{ $job->check_out_lat }}" data-lng="{{ $job->check_out_lng }}">
                <x-ui-icon name="map-pin" />
                <span>Completed from: <span id="endLocationText">Loading address...</span></span>
            </div>
        @endif

        <p class="fw-bold mb-2" style="color:{{ $color }};">Job Information</p>

        @if($job->displayCode())
            <div class="row mb-2">
                <div class="col-6">
                    <p class="act-field-label mb-0">Job ID</p>
                    <p class="act-info-value mb-0">{{ $job->displayCode() }}</p>
                </div>
                <div class="col-6">
                    <p class="act-field-label mb-0">Unit ID</p>
                    <p class="act-info-value mb-0">{{ optional($job->unit)->identification_no ?? '—' }}{{ optional($job->unit)->unit_number ? ' (' . $job->unit->unit_number . ')' : '' }}</p>
                </div>
            </div>
        @else
            <div class="row mb-2">
                <div class="col-6">
                    <p class="act-field-label mb-0">Unit ID</p>
                    <p class="act-info-value mb-0">{{ optional($job->unit)->identification_no ?? '—' }}{{ optional($job->unit)->unit_number ? ' (' . $job->unit->unit_number . ')' : '' }}</p>
                </div>
                <div class="col-6">
                    <p class="act-field-label mb-0">Asset / Unit</p>
                    <p class="act-info-value mb-0">{{ optional($job->unit)->unit_type ?? '—' }}</p>
                </div>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-12">
                <p class="act-field-label mb-0">Location</p>
                <p class="act-info-value mb-0">{{ optional($job->contract)->location ?? '—' }} — {{ optional($job->contract)->project_name ?? '—' }}</p>
            </div>
        </div>

        @if($job->job_type === 'casual_job')
            <div class="row mb-2">
                <div class="col-6">
                    <p class="act-field-label mb-0">Customer</p>
                    <p class="act-info-value mb-0">{{ $job->customer_name ?? '—' }}</p>
                </div>
                <div class="col-6">
                    <p class="act-field-label mb-0">Quoted Cost</p>
                    <p class="act-info-value mb-0">{{ $job->cost ? number_format($job->cost, 2) : '—' }}</p>
                </div>
            </div>
            <p class="text-muted small mb-3">
                <x-ui-icon name="lock" /> Customer and cost are set by the office and cannot be changed here.
            </p>
        @endif

        @if($job->status === 'completed')
            {{-- Read-only completed view --}}
            @if($job->start_photo)
                <p class="act-field-label mb-1">Start Photo</p>
                <img src="{{ storage_asset($job->start_photo) }}" class="img-fluid rounded mb-3">
            @endif

            @if($job->job_type === 'breakdown')
                <p class="act-field-label mb-0">Breakdown Cause</p>
                <p class="mb-3">{{ $job->breakdown_cause ?? '—' }}</p>
            @endif
            @if($job->job_type === 'repair')
                <p class="act-field-label mb-0">Warranty / Contract</p>
                <p class="mb-3">{{ $job->warranty_contract ?? '—' }}</p>
            @endif

            <p class="act-field-label mb-0">Issue Description</p>
            <p class="mb-3">{{ $job->description ?? '—' }}</p>

            @if($job->job_type === 'breakdown')
                <p class="act-field-label mb-0">Immediate Action Taken</p>
                <p class="mb-3">{{ $job->immediate_action_taken ?? '—' }}</p>
            @endif

            @if($job->unit_status_after)
                <p class="act-field-label mb-0">Elevator Status at Closure</p>
                <p class="mb-3 fw-bold" style="color: {{ $job->unit_status_after === 'shutdown' ? '#D64545' : '#2E9E5B' }};">
                    {{ $job->unit_status_after === 'shutdown' ? 'Shutdown' : 'Working' }}
                    @if($job->shutdown_reason)
                        <span class="fw-normal text-dark">— {{ $job->shutdown_reason }}</span>
                    @endif
                </p>
            @endif

            <p class="act-field-label mb-0">Work Performed</p>
            <p class="mb-3">{{ $job->work_notes ?? '—' }}</p>

            @if(!empty($job->parts_used))
                <p class="act-field-label mb-0">Parts Used</p>
                <p class="mb-3">{{ implode(', ', $job->parts_used) }}</p>
            @endif

            @if($job->completion_photo)
                <p class="act-field-label mb-1">Completion Photo</p>
                <img src="{{ storage_asset($job->completion_photo) }}" class="img-fluid rounded mb-3">
            @endif

            @if($job->signature_path)
                <p class="act-field-label mb-1">Signature</p>
                <img src="{{ storage_asset($job->signature_path) }}" class="img-fluid rounded mb-3" style="max-height:100px; background:#f5f7fa;">
            @endif

        @elseif(! $job->check_in_time)
            {{-- STEP: Not started — must capture a start photo, which then auto-captures location + time --}}
            <p class="text-muted small mb-3">
                Tap below to take a photo — your location and start time will be captured automatically right after.
            </p>
            <label class="start-photo-btn mb-0">
                <x-ui-icon name="camera" />
                <span id="startBtnLabel">Take Photo &amp; Start</span>
                <input type="file" accept="image/*" capture="environment" class="d-none" id="startPhotoInput">
            </label>
            <div id="startLocationPreview" class="location-box mt-3" style="display:none;">
                <x-ui-icon name="map-pin" />
                <span id="startLocationPreviewText"></span>
            </div>

        @else
            {{-- STEP: In progress — full form unlocked --}}
            <form id="activityForm">
                @if($job->job_type === 'breakdown')
                    <p class="act-field-label mb-1">Breakdown Cause @if($job->parent_job_id)<span class="text-muted fw-normal">(optional — follows the main job)</span>@endif</p>
                    <select name="breakdown_cause" class="form-select mb-3" {{ $job->parent_job_id ? '' : 'required' }}>
                        <option value="">Select Breakdown Cause</option>
                        @foreach(\App\Http\Controllers\Technician\TechnicianJobController::BREAKDOWN_CAUSES as $cause)
                            <option value="{{ $cause }}" {{ $job->breakdown_cause === $cause ? 'selected' : '' }}>{{ $cause }}</option>
                        @endforeach
                    </select>
                @endif

                @if($job->job_type === 'repair')
                    <p class="act-field-label mb-1">Warranty / Contract</p>
                    <select name="warranty_contract" class="form-select mb-3">
                        <option value="">Select</option>
                        <option value="Warranty" {{ $job->warranty_contract === 'Warranty' ? 'selected' : '' }}>Warranty</option>
                        <option value="Contract" {{ $job->warranty_contract === 'Contract' ? 'selected' : '' }}>Contract</option>
                        <option value="Chargeable" {{ $job->warranty_contract === 'Chargeable' ? 'selected' : '' }}>Chargeable</option>
                    </select>
                @endif

                @if($job->job_type === 'casual_job')
                    {{-- Locked back-office fields — still submitted so validation passes, but not editable --}}
                    <input type="hidden" name="customer_name" value="{{ $job->customer_name }}">
                    <input type="hidden" name="cost" value="{{ $job->cost }}">
                @endif

                <p class="act-field-label mb-1">Issue Description</p>
                <textarea name="description" class="form-control mb-3" rows="3" placeholder="Describe the issue / requirement" required>{{ $job->description }}</textarea>

                @if($job->job_type === 'breakdown')
                    <p class="act-field-label mb-1">Immediate Action Taken</p>
                    <textarea name="immediate_action_taken" class="form-control mb-3" rows="2" placeholder="Enter action taken">{{ $job->immediate_action_taken }}</textarea>
                @endif

                <p class="act-field-label mb-1">Work Performed</p>
                <textarea name="work_notes" class="form-control mb-3" rows="3" placeholder="Enter work performed details">{{ $job->work_notes }}</textarea>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <p class="act-field-label mb-0">Parts Used (if any)</p>
                    <button type="button" id="addPartBtn" class="btn btn-sm" style="color:{{ $color }}; border:1px solid {{ $color }};">+ Add Parts</button>
                </div>
                <div id="partsContainer" class="mb-3">
                    @foreach($job->parts_used ?? [] as $part)
                        <div class="parts-row">
                            <input type="text" name="parts_used[]" class="form-control" value="{{ $part }}" placeholder="Part name">
                            <button type="button" class="btn btn-outline-danger remove-part-btn">✕</button>
                        </div>
                    @endforeach
                </div>

                @if(in_array($job->job_type, ['breakdown', 'repair']))
                    <p class="act-field-label mb-1">
                        Elevator Status After {{ $job->job_type === 'breakdown' ? 'Breakdown' : 'Repair' }}
                        @if($job->job_type === 'breakdown')<span class="text-danger">*</span>@else<span class="text-muted fw-normal">(optional)</span>@endif
                    </p>
                    <div class="status-choice">
                        <label>
                            <input type="radio" name="unit_status_after" value="working" {{ $job->unit_status_after === 'working' ? 'checked' : '' }}>
                            <x-ui-icon name="check-circle" size="18" stroke="2.2" /> Working
                        </label>
                        <label>
                            <input type="radio" name="unit_status_after" value="shutdown" {{ $job->unit_status_after === 'shutdown' ? 'checked' : '' }}>
                            <x-ui-icon name="power" size="18" stroke="2.2" /> Shutdown
                        </label>
                    </div>
                    <div id="shutdownReasonWrap" style="{{ $job->unit_status_after === 'shutdown' ? '' : 'display:none;' }}">
                        <p class="act-field-label mb-1">Reason for Shutdown <span class="text-danger">*</span></p>
                        <textarea name="shutdown_reason" class="form-control mb-3" rows="2" maxlength="500"
                            placeholder="Why is the elevator shut down? e.g. waiting for controller board">{{ $job->shutdown_reason }}</textarea>
                    </div>
                @endif

                <p class="act-field-label mb-2">Completion Attachments</p>
                <div class="attach-row mb-2">
                    <label class="attach-btn mb-0">
                        <x-ui-icon name="camera" />
                        Add Photo
                        <input type="file" name="completion_photo" accept="image/*" capture="environment" class="d-none" id="photoInput">
                    </label>
                    <label class="attach-btn mb-0">
                        <x-ui-icon name="paperclip" />
                        Add File
                        <input type="file" class="d-none" id="fileInput">
                    </label>
                    <div class="attach-btn" id="sigTrigger">
                        <x-ui-icon name="pen" />
                        Signature
                    </div>
                </div>
                <div id="photoPreview" class="mb-2"></div>

                <div id="sigWrapper" style="display:none;" class="mb-3">
                    <canvas id="sigPad" height="150"></canvas>
                    <div class="d-flex justify-content-between mt-1">
                        <button type="button" id="clearSigBtn" class="btn btn-sm btn-outline-secondary">Clear</button>
                        <span class="small text-muted">Sign above</span>
                    </div>
                </div>

                <label class="act-field-label mb-1">Your Current Location</label>
                <input type="text" id="completeLocationDisplay" class="form-control readonly-field mb-3" readonly
                    placeholder="Location will be captured when you tap Complete Activity">

                <p class="small text-muted mb-3">
                    <x-ui-icon name="alert" />
                    You must be within 200m of your start location to complete this activity.
                </p>
            </form>

            <div class="act-footer-btns">
                <button type="button" id="saveDraftBtn" class="btn btn-outline-secondary flex-fill py-3 fw-bold">Save</button>
                <button type="button" id="completeBtn" class="btn flex-fill py-3 fw-bold text-white" style="background:{{ $color }};">
                    Complete Activity
                </button>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        const messageBox = document.getElementById('message');
        function showError(msg) { messageBox.innerHTML = `<div class="alert alert-danger">${msg}</div>`; window.scrollTo({top:0, behavior:'smooth'}); }
        function showSuccess(msg) { messageBox.innerHTML = `<div class="alert alert-success">${msg}</div>`; }

        // ===== Reverse geocoding (free, no API key) — used for both display and history =====
        async function reverseGeocode(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                const data = await response.json();
                return data.display_name || `${lat}, ${lng}`;
            } catch (err) {
                return `${lat}, ${lng}`;
            }
        }

        // Show readable address for already-recorded start/end locations
        const startBox = document.getElementById('startLocationBox');
        if (startBox) {
            reverseGeocode(startBox.dataset.lat, startBox.dataset.lng).then(address => {
                document.getElementById('startLocationText').textContent = address;
            });
        }
        const endBox = document.getElementById('endLocationBox');
        if (endBox) {
            reverseGeocode(endBox.dataset.lat, endBox.dataset.lng).then(address => {
                document.getElementById('endLocationText').textContent = address;
            });
        }

        document.getElementById('addPartBtn')?.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'parts-row';
            row.innerHTML = `<input type="text" name="parts_used[]" class="form-control" placeholder="Part name">
                <button type="button" class="btn btn-outline-danger remove-part-btn">✕</button>`;
            document.getElementById('partsContainer').appendChild(row);
            row.querySelector('.remove-part-btn').addEventListener('click', () => row.remove());
        });
        document.querySelectorAll('.remove-part-btn').forEach(btn => {
            btn.addEventListener('click', function() { this.closest('.parts-row').remove(); });
        });

        document.getElementById('photoInput')?.addEventListener('change', function() {
            const preview = document.getElementById('photoPreview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height:180px;">`;
                reader.readAsDataURL(this.files[0]);
            }
        });

        let sigCanvas, sigCtx, drawing = false, hasSignature = false;

        document.getElementById('sigTrigger')?.addEventListener('click', function() {
            const wrapper = document.getElementById('sigWrapper');
            wrapper.style.display = wrapper.style.display === 'none' ? 'block' : 'none';
            if (wrapper.style.display === 'block' && !sigCanvas) initSigPad();
        });

        function initSigPad() {
            sigCanvas = document.getElementById('sigPad');
            sigCanvas.width = sigCanvas.offsetWidth;
            sigCtx = sigCanvas.getContext('2d');
            sigCtx.strokeStyle = '#16233b';
            sigCtx.lineWidth = 2;
            sigCtx.lineCap = 'round';

            const pos = e => {
                const rect = sigCanvas.getBoundingClientRect();
                const point = e.touches ? e.touches[0] : e;
                return { x: point.clientX - rect.left, y: point.clientY - rect.top };
            };

            const start = e => { drawing = true; hasSignature = true; const p = pos(e); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); };
            const move = e => { if (!drawing) return; e.preventDefault(); const p = pos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); };
            const end = () => { drawing = false; };

            sigCanvas.addEventListener('mousedown', start);
            sigCanvas.addEventListener('mousemove', move);
            sigCanvas.addEventListener('mouseup', end);
            sigCanvas.addEventListener('touchstart', start);
            sigCanvas.addEventListener('touchmove', move);
            sigCanvas.addEventListener('touchend', end);
        }

        document.getElementById('clearSigBtn')?.addEventListener('click', function() {
            sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
            hasSignature = false;
        });

        function getLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) { reject('Location not supported on this device.'); return; }
                let best = null;
                const watchId = navigator.geolocation.watchPosition(
                    pos => {
                        if (!best || pos.coords.accuracy < best.accuracy) {
                            best = { latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: pos.coords.accuracy };
                        }
                        if (pos.coords.accuracy <= 50) { navigator.geolocation.clearWatch(watchId); resolve(best); }
                    },
                    () => { navigator.geolocation.clearWatch(watchId); reject('Could not access your location.'); },
                    { enableHighAccuracy: true, maximumAge: 0 }
                );
                setTimeout(() => {
                    navigator.geolocation.clearWatch(watchId);
                    best ? resolve(best) : reject('Could not get a location fix.');
                }, 8000);
            });
        }

        // ===== Start flow: photo → auto-fetch location → show it → submit → unlock form =====
        const startPhotoInput = document.getElementById('startPhotoInput');
        startPhotoInput?.addEventListener('change', async function() {
            if (!this.files || !this.files[0]) return;

            const label = document.getElementById('startBtnLabel');
            const startPhoto = this.files[0];
            const previewBox = document.getElementById('startLocationPreview');
            const previewText = document.getElementById('startLocationPreviewText');

            label.textContent = 'Getting your location...';

            let loc;
            try {
                loc = await getLocation();
            } catch (err) {
                showError(err);
                label.textContent = 'Take Photo & Start';
                this.value = '';
                return;
            }

            // Show the fetched location immediately, before submitting
            previewBox.style.display = 'flex';
            previewText.textContent = 'Detecting address...';
            reverseGeocode(loc.latitude, loc.longitude).then(address => {
                previewText.textContent = address;
            });

            label.textContent = 'Starting...';

            const formData = new FormData();
            formData.append('start_photo', startPhoto);
            formData.append('latitude', loc.latitude);
            formData.append('longitude', loc.longitude);

            fetch("{{ route('technician.activities.start', $job->id) }}", {
                    method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        showError(Object.values(data.errors).flat().join('<br>'));
                        label.textContent = 'Take Photo & Start';
                        startPhotoInput.value = '';
                    }
                })
                .catch(() => {
                    showError('Something went wrong. Please try again.');
                    label.textContent = 'Take Photo & Start';
                    startPhotoInput.value = '';
                });
        });

        // ===== Save as Draft =====
        document.getElementById('saveDraftBtn')?.addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            const formData = new FormData(document.getElementById('activityForm'));

            fetch("{{ route('technician.activities.save-draft', $job->id) }}", {
                    method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) { showSuccess(data.message); setTimeout(() => window.location.href = "{{ route('technician.jobs.index') }}", 1000); }
                    else { showError(Object.values(data.errors).flat().join('<br>')); }
                });
        });

        // ===== Complete flow: requires photo + signature → auto-fetch location → show it → submit =====
        // ===== Working / Shutdown closure choice =====
        document.querySelectorAll('input[name="unit_status_after"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('shutdownReasonWrap').style.display = this.value === 'shutdown' ? '' : 'none';
            });
        });

        document.getElementById('completeBtn')?.addEventListener('click', async function() {
            const btn = this;
            @if($job->job_type === 'breakdown')
                const unitStatus = document.querySelector('input[name="unit_status_after"]:checked');
                if (!unitStatus) { showError('Please select whether the elevator is Working or Shutdown.'); return; }
                if (unitStatus.value === 'shutdown' && !document.querySelector('textarea[name="shutdown_reason"]').value.trim()) {
                    showError('Please enter the reason for the shutdown.'); return;
                }
            @endif
            if (!document.getElementById('photoInput').files.length) { showError('Please attach a completion photo before completing.'); return; }
            if (!hasSignature) { showError('Please provide a signature before completing.'); return; }

            btn.disabled = true;
            btn.innerText = 'Getting location...';

            let loc;
            try {
                loc = await getLocation();
            } catch (err) {
                showError(err);
                btn.disabled = false;
                btn.innerText = 'Complete Activity';
                return;
            }

            // Show the fetched location in the visible field before submitting
            const locationField = document.getElementById('completeLocationDisplay');
            locationField.value = 'Detecting address...';
            reverseGeocode(loc.latitude, loc.longitude).then(address => {
                locationField.value = address;
            });

            btn.innerText = 'Submitting...';

            const formData = new FormData(document.getElementById('activityForm'));
            formData.append('signature', sigCanvas.toDataURL('image/png'));
            formData.append('latitude', loc.latitude);
            formData.append('longitude', loc.longitude);

            fetch("{{ route('technician.activities.complete', $job->id) }}", {
                    method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { window.location.href = data.redirect; }
                    else { showError(Object.values(data.errors).flat().join('<br>')); btn.disabled = false; btn.innerText = 'Complete Activity'; }
                });
        });
    </script>
    @endpush
@endsection