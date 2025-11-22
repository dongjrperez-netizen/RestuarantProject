<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Order Response</title>
    <style>
      body {
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
        padding: 2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        margin: 0;
      }
      .card {
        max-width: 600px;
        margin: 2rem auto;
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
      }
      .icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
      }
      .icon.success {
        background: #d1fae5;
        color: #10b981;
      }
      .icon.danger {
        background: #fee2e2;
        color: #ef4444;
      }
      .title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-align: center;
      }
      .po-number {
        text-align: center;
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
      }
      .message {
        text-align: center;
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 8px;
      }
      .footer {
        text-align: center;
        color: #6b7280;
        font-size: 0.875rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
      }
    </style>
  </head>
  <body>
    <div class="card">
      <div class="icon {{ $action === 'confirm' ? 'success' : 'danger' }}">
        {{ $action === 'confirm' ? '✓' : '✕' }}
      </div>
      <div class="title">{{ $action === 'confirm' ? 'Order Confirmed!' : 'Order Rejected' }}</div>
      <div class="po-number">Purchase Order: <strong>{{ $po_number }}</strong></div>
      <div class="message">{{ $message }}</div>
      <div class="footer">
        <p>If you have any questions or concerns, please contact the restaurant directly.</p>
        <p><strong>Thank you for your response!</strong></p>
      </div>
    </div>
  </body>
</html>
