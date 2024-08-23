@php
    use Ramsey\Uuid\Uuid;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payment Flow</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- link to the Square web payment SDK library -->
    <script type="text/javascript" src="{{ $web_payment_sdk_url }}"></script>
    <script type="text/javascript">
        window.applicationId = "{{ env('SQUARE_APPLICATION_ID') }}";
        window.locationId = "{{ env('SQUARE_LOCATION_ID') }}";
        window.currency = "{{ $location_info->getCurrency() }}";
        window.country = "{{ $location_info->getCountry() }}";
        window.idempotencyKey = "{{ Uuid::uuid4() }}";
    </script>
    <link rel="stylesheet" type="text/css" href="{{ asset('stylesheets/style.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('stylesheets/sq-payment.css') }}">
</head>

<body>
    <form class="payment-form" id="fast-checkout">

        <div class="wrapper">
            <div id="apple-pay-button" alt="apple-pay" type="button"></div>
            <div id="google-pay-button" alt="google-pay" type="button"></div>
            <div class="border">
                <span>OR</span>
            </div>
            <div id="ach-wrapper">
                <label for="ach-account-holder-name">Full Name</label>
                <input id="ach-account-holder-name" type="text" placeholder="Enter your full name"
                    name="ach-account-holder-name" autocomplete="name" />
                <span id="ach-message"></span>
                <button id="ach-button" type="button">Pay with Bank Account</button>

                <div class="border">
                    <span>OR</span>
                </div>
            </div>
            <div id="card-container"></div>
            <button id="card-button" type="button">Pay with Card</button>
            <span id="payment-flow-message"></span>
        </div>
    </form>

    <script type="text/javascript" src="{{ asset('js/sq-ach.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/sq-apple-pay.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/sq-card-pay.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/sq-google-pay.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/sq-payment-flow.js') }}"></script>
</body>

</html>
