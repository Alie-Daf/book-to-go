<div class="card-box mt-4">
    <h2 class="section-title">My data (Data Privacy Act of 2012)</h2>
    <p class="text-muted">You may download a copy of your personal data, or delete your account.</p>

    <a href="{{ route('privacy.export') }}" class="btn btn-outline-primary mb-3"><i class="bi bi-download"></i> Download my data</a>

    <form class="ajax-form" action="{{ route('privacy.delete') }}">
        <label class="form-label">Delete my account: type your password to confirm</label>
        <div class="input-group">
            <input type="password" name="password" class="form-control" required>
            <button class="btn btn-outline-danger" onclick="return confirm('Delete your account? This cannot be undone.')">Delete account</button>
        </div>
        <small class="text-muted">Your name, email and documents are erased. Bookings and invoices are kept without your name for accounting.</small>
    </form>
</div>