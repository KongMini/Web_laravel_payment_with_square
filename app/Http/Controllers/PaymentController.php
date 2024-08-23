<?php

namespace App\Http\Controllers\FrontEnd;


use Illuminate\Http\Request;
use Square\SquareClient;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;
use Square\Exceptions\ApiException;
use Ramsey\Uuid\Uuid;
use App\Services\LocationInfo;
use Square\Environment;
// use log
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $locationInfo;

    public function __construct(LocationInfo $locationInfo)
    {
        $this->locationInfo = $locationInfo;
    }  

    public function index()
    {
        // Lấy thông tin từ LocationInfo
        $location_info = $this->locationInfo;

        // Pulled from the .env file and upper cased e.g. SANDBOX, PRODUCTION.
        $upper_case_environment = strtoupper(env('ENVIRONMENT'));
        $web_payment_sdk_url = env('ENVIRONMENT') === Environment::PRODUCTION ? "https://web.squarecdn.com/v1/square.js" : "https://sandbox.web.squarecdn.com/v1/square.js";

        // Truyền biến vào view
        return view('frontend.pages.payment.index', [
            'location_info' => $location_info,
            'web_payment_sdk_url' => $web_payment_sdk_url
        ]);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'idempotencyKey' => 'required|string',
        ]);

        $square_client = new SquareClient([
            'accessToken' => env('SQUARE_ACCESS_TOKEN'),
            'environment' => env('ENVIRONMENT'),
            'userAgentDetail' => 'sample_app_php_payment',
        ]);

        $payments_api = $square_client->getPaymentsApi();
        $location_info = $this->locationInfo;

        $money = new Money();
        $money->setAmount(12345); // Số tiền cần thanh toán (ví dụ: 10000 đồng)
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
}
