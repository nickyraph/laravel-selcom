<?php

namespace Bryceandy\Selcom\Tests\Feature\Checkout;

use Bryceandy\Selcom\{
    Exceptions\InvalidDataException,
    Exceptions\MissingDataException,
    Facades\Selcom,
    Tests\TestCase,
};
use Illuminate\{
    Foundation\Testing\WithFaker,
    Http\RedirectResponse,
    Support\Arr,
    Support\Facades\Http,
};

class CheckoutTest extends TestCase
{
    use WithFaker;

    private array $requiredCheckoutData;

    private array $cardCheckoutData;

    private array $walletPaymentResponseData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiredCheckoutData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'amount' => $this->faker->randomNumber(5),
            'transaction_id' => strtoupper($this->faker->bothify('##???#??#???')),
        ];

        $this->cardCheckoutData = array_merge($this->requiredCheckoutData, [
            'address' => $this->faker->address(),
            'postcode' => $this->faker->postcode(),
        ]);

        $createOrderResponse = Http::response(json_decode(
            file_get_contents(__DIR__ . '/../../stubs/create-order-response.json'),
            true
        ));

        $this->walletPaymentResponseData = json_decode(
            file_get_contents(__DIR__ . '/../../stubs/wallet-payment-response.json'),
            true
        );

        $urlPrefix = 'https://apigwtest.selcommobile.com/v1/';

        Http::fake([
            "${urlPrefix}checkout/create-order-minimal" => $createOrderResponse,
            "${urlPrefix}checkout/create-order" => $createOrderResponse,
            "${urlPrefix}checkout/wallet-payment" => Http::response($this->walletPaymentResponseData),
        ]);
    }

    /** @test */
    public function test_sending_incomplete_checkout_data_throws_an_exception()
    {
        $this->expectException(MissingDataException::class);

        Selcom::checkout(Arr::except(
            $this->requiredCheckoutData,
            Arr::random(array_keys($this->requiredCheckoutData))
        ));

        $response = Selcom::checkout($this->requiredCheckoutData);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /** @test */
    public function test_sending_incomplete_card_checkout_data_throws_an_exception()
    {
        $this->expectException(MissingDataException::class);

        Selcom::cardCheckout(Arr::except(
            $this->cardCheckoutData,
            Arr::random(array_keys($this->cardCheckoutData))
        ));

        $response = Selcom::cardCheckout($this->cardCheckoutData);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /** @test */
    public function test_sending_incomplete_checkout_name_throws_an_exception()
    {
        $this->expectException(InvalidDataException::class);

        $this->expectExceptionMessage('Name must contain at-least 2 words');

        $data = $this->requiredCheckoutData;

        $data['name'] = 'Bryce';

        Selcom::checkout($data);
    }

    /** @test */
    public function test_sending_incomplete_card_checkout_name_throws_an_exception()
    {
        $this->expectException(InvalidDataException::class);

        $this->expectExceptionMessage('Name must contain at-least 2 words');

        $data = $this->cardCheckoutData;

        $data['name'] = 'Bryce';

        Selcom::cardCheckout($data);
    }

    /** @test */
    public function test_ussd_checkout_sends_back_data_without_redirecting()
    {
        $response = Selcom::checkout(array_merge(
            $this->requiredCheckoutData,
            ['no_redirection' => true]
        ));

        $this->assertEquals($response, $this->walletPaymentResponseData);
    }

    /** @test */
    public function test_automatic_card_checkout_requires_user_data()
    {
        $this->expectException(InvalidDataException::class);

        $data = $this->cardCheckoutData;

        $data['no_redirection'] = true;

        Selcom::cardCheckout($data);
    }
}
