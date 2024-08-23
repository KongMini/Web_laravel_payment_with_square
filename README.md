# Tích hợp Thanh toán Laravel với API Square

Dự án này tích hợp Laravel với API Square để xử lý thanh toán, với đơn vị tiền tệ được cấu hình là "VND" (Đồng Việt Nam).

## Yêu cầu trước khi bắt đầu

Trước khi bắt đầu, hãy đảm bảo rằng bạn đã cài đặt:

- **PHP**: Phiên bản 7.4 hoặc cao hơn
- **Composer**: Trình quản lý phụ thuộc cho PHP
- **Laravel**: Phiên bản 7 hoặc cao hơn
- **Tài khoản API Square**: Để xử lý thanh toán. Truy cập vào https://developer.squareup.com để đăng ký tài khoản và tạo ứng dụng

## Cài đặt

### 1. Cài đặt thư viện cần thiết để tích hợp

Cài đặt thư viện cần thiết về máy của bạn bằng lệnh sau:

```bash
    composer require ramsey/uuid square/square
```

Kiểm tra trong file composer.json đã có thư viện được cài đặt

```json
    "require": {
        "ramsey/uuid": "^4.2",
        "square/square": "^38.0",
    }
```

### 2. Tạo thư mục app/Services để 

```bash
    mkdir app/Services
```

#### 2.1 Tạo file LocationInfo.php trong thư mục app/Services

```php
    <?php
        namespace App\Services;

        use Dotenv\Dotenv;
        use Square\SquareClient;
        use Square\Environment;

        class LocationInfo
        {
            private $currency;
            private $country;
            private $location_id;

            public function __construct()
            {
                $dotenv = Dotenv::createImmutable(base_path());
                $dotenv->load();

                $access_token = env('SQUARE_ACCESS_TOKEN');
                $square_client = new SquareClient([
                    'accessToken' => $access_token,
                    'environment' => env('ENVIRONMENT'),
                ]);

                $location_api = $square_client->getLocationsApi();
                $response = $location_api->retrieveLocation(env('SQUARE_LOCATION_ID'));

                if ($response->isSuccess()) {
                    $location = $response->getResult()->getLocation();
                    $this->location_id = $location->getId();
                    $this->currency = $location->getCurrency(); // Mặc định là USD
                    $this->country = $location->getCountry();

                    // Có thể Ghi đè giá trị currency thành 'VND'
                    // $this->currency = 'VND';
                } else {
                    // Handle errors
                    throw new \Exception('Unable to retrieve location from Square API.');
                }
            }

            public function getCurrency()
            {
                return $this->currency;
            }

            public function getCountry()
            {
                return $this->country;
            }

            public function getId()
            {
                return $this->location_id;
            }
        }
```

### 3. Tạo Controller mới, hoặc có thể viết vào Controller đã có sẵn

#### 3.1.1 Tạo Controller cho Newway


```bash
    php artisan make:controller FrontEnd/PaymentController 
```

#### 3.1.2 Tạo Controller cho Laravel bình thường

```bash
    php artisan make:controller PaymentController
```

#### 3.2 Chỉnh sửa hàm index trong file PaymentController.php trong thư mục app/Http/Controllers để truyền biến vào view

```php
    public function index()
    {
        // Lấy thông tin từ LocationInfo
        $location_info = $this->locationInfo;

        // Pulled from the .env file and upper cased e.g. SANDBOX, PRODUCTION.
        $upper_case_environment = strtoupper(env('ENVIRONMENT'));
        $web_payment_sdk_url = env('ENVIRONMENT') === Environment::PRODUCTION ? "https://web.squarecdn.com/v1/square.js" : "https://sandbox.web.squarecdn.com/v1/square.js";

        // Truyền biến vào view, ruyền vào file blade đi tới(ontend.pages.payment.index')
        return view('frontend.pages.payment.index', [

            'location_info' => $location_info,
            'web_payment_sdk_url' => $web_payment_sdk_url
        ]);
    }
```

#### 3.3 Chỉnh sửa hàm processPayment trong file PaymentController.php trong thư mục app/Http/Controllers

```php
    public function processPayment(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'idempotencyKey' => 'required|string',
            // 'amount' => 'required|numeric',// Số tiền cần thanh toán
        ]);

        $square_client = new SquareClient([
            'accessToken' => env('SQUARE_ACCESS_TOKEN'),
            'environment' => env('ENVIRONMENT'),
            'userAgentDetail' => 'sample_app_php_payment',//Nội dung thanh toán
        ]);

        $payments_api = $square_client->getPaymentsApi();
        $location_info = $this->locationInfo;

        $money = new Money();
        // Thay đổi thành số tiền cần thanh toán có thể lấy từ request
        $money->setAmount(12345); //Cách 1: truyền trực tiếp số tiền cần thanh toán
        
        // $money->setAmount($request->input('amount'));//Cách 2: truyền từ request

        // Thay đổi thành đơn vị tiền tệ có thể lấy từ request(Mặc định lấy từ API của ứng dụng Square)
        $money->setCurrency($location_info->getCurrency());
        
        try {
            $create_payment_request = new CreatePaymentRequest(
                $request->input('token'),
                $request->input('idempotencyKey'),
            );
            $create_payment_request->setLocationId($location_info->getId());
            $create_payment_request->setAmountMoney($money);
            \Log::info('create_payment_request Object: ' . print_r($create_payment_request, true));
            $response = $payments_api->createPayment($create_payment_request);

            if ($response->isSuccess()) {
                return response()->json($response->getResult());
            } else {
                return response()->json(['errors' => $response->getErrors()], 400);
            }
        } catch (ApiException $e) {
            // Log exception details
            \Log::error('Square API Error: ' . $e->getMessage());
            return response()->json(['errors' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Log exception details
            \Log::error('General Error: ' . $e->getMessage());
            return response()->json(['errors' => 'An error occurred.'], 500);
        }
    }
```


### 4. Thêm biến môi trường cho ứng dụng Laravel các thông tin được lấy từ tài khoản API Square


```env
# Thay đổi sandbox: môi trường test, production để sử dụng dự án thực tế
ENVIRONMENT=sandbox

# Lấy trong phần https://developer.squareup.com/apps/{id_app}/settings
SQUARE_APPLICATION_ID=sandbox-sq0idb-Lls7dmWvu_COpb9aSf6vUQ
SQUARE_ACCESS_TOKEN=EAAAl3wiO3BL8Vhqw4fNw3N920lkWLdFNNig1YR4_wkfL3C7aD4_6DayQGw9d7kM
SQUARE_LOCATION_ID=LXFYDFZR6MRGF
```

### 5. Thêm file js, stylesheet vào dự án

#### 5.1 Chỉnh sửa lại file sq-payment-flow.js trong thư mục public/js để phù hợp với request gửi tới Controller xử lý

```js
    window.createPayment = async function (token) {
        // Setup CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const dataJsonString = JSON.stringify({
            token,
            idempotencyKey: window.idempotencyKey,
            // Có thể thêm số tiền cần thanh toán vào request lấy từ input hoặc bất kỳ đâu
            
        });

        try {
            const response = await fetch('/process-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: dataJsonString
            });

            const data = await response.json();

            if (data.errors && data.errors.length > 0) {
            if (data.errors[0].detail) {
                window.showError(data.errors[0].detail);
            } else {
                window.showError('Payment Failed.');
            }
            } else {
            window.showSuccess('Payment Successful!');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
```

### 6. Tạo route cho dự án

#### 6.1 Laravel 7   và dự án của Newway

```php
  Route::get('/payment', 'PaymentController@index');
  Route::post('/process-payment', 'PaymentController@processPayment');
```

#### 6.2 Laravel 8 trở lên

```php
    use App\Http\Controllers\PaymentController;

    Route::get('/payment', [PaymentController::class, 'index']);
    Route::post('/process-payment', [PaymentController::class, 'processPayment']);
```

### 7. Tạo view cho dự án trong file payment.blade.php trong thư mục resources/views

```html
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
                <input id="ach-account-holder-name" type="text" placeholder="Jane Doe" name="ach-account-holder-name"
                    autocomplete="name" />
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


```

### 8. Chạy dự án

#### 8.1 Laravel chạy bằng php artisan serve

```bash
    php artisan serve
```



