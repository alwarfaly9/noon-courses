<?php

$file = __DIR__ . '/app/Http/Controllers/PaymentController.php';
$content = file_get_contents($file);

$startStr = 'public function redeemCreditCard';
$endStr = 'public function myTransactions';

$startPos = strpos($content, $startStr);
$endPos = strpos($content, $endStr);

if ($startPos !== false && $endPos !== false) {
    $newMethod = "public function redeemCreditCard(RedeemCreditCardRequest \$request)
    {
        try {
            \$result = \$this->paymentService->redeemCreditCard(\$request->user(), \$request->serial_number);
            return response()->json([
                'success' => true,
                'message' => 'Credit card redeemed successfully',
                'data' => \$result
            ]);
        } catch (\\Exception \$e) {
            return response()->json([
                'success' => false,
                'message' => \$e->getMessage()
            ], 400);
        }
    }

    // Get my transactions
    ";

    // remove '    // Get my transactions' and space above it if needed, we'll just replace up to endPos
    $content = substr_replace($content, $newMethod, $startPos, $endPos - $startPos);
    file_put_contents($file, $content);
    echo "Patch successful\n";
} else {
    echo "Positions not found\n";
}