<?php
namespace Payum\Stripe\Tests\Extension;

use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Generic;
use Payum\Core\Request\Refund;
use Payum\Stripe\Constants;
use Payum\Stripe\Extension\CreateCustomerExtension;
use Payum\Stripe\Request\Api\CreateCustomer;
use Payum\Stripe\Request\Api\ObtainToken;
use Payum\Stripe\Request\Api\RetrieveCustomer;
use Payum\Stripe\Request\Api\CreateCustomerSource;
use Payum\Stripe\Request\Api\RetrieveToken;

class CreateCustomerExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldImplementExtensionInterface()
    {
        $rc = new \ReflectionClass(CreateCustomerExtension::class);

        $this->assertTrue($rc->implementsInterface(ExtensionInterface::class));
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new CreateCustomerExtension();
    }

    public function testShouldCreateCustomerAndReplaceCardTokenOnPreCapture()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => ['save_card' => true],
        ]);
        $request = new Capture($model);
        
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(CreateCustomer::class))
            ->willReturnCallback(function(CreateCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals(['source' => 'tok_xxx'], (array) $model);

                $model['id'] = 'theCustomerId';
                $model['default_source'] = 'card_xxx';
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'customer' => 'theCustomerId',
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => 'theCustomerId',
                    'source' => 'tok_xxx',
                    'default_source' => 'card_xxx',
                ],
                'card_id' => 'card_xxx',
            ],
            'source' => 'card_xxx',
        ], (array) $request->getModel());
    }

    public function testShouldCreateCustomerWithCustomInfoAndReplaceCardTokenOnPreCapture()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
                'customer' => ['foo' => 'fooVal', 'bar' => 'barVal'],
            ],
        ]);
        $request = new Capture($model);

        $gatewayMock = $this->createGatewayMock();

        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(CreateCustomer::class))
            ->willReturnCallback(function(CreateCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals([
                    'source' => 'tok_xxx',
                    'foo' => 'fooVal',
                    'bar' => 'barVal'
                ], (array) $model);

                $model['id'] = 'theCustomerId';
                $model['default_source'] = 'card_xxx';
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'customer' => 'theCustomerId',
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => 'theCustomerId',
                    'source' => 'tok_xxx',
                    'default_source' => 'card_xxx',
                    'foo' => 'fooVal',
                    'bar' => 'barVal'
                ],
                'card_id' => 'card_xxx',
            ],
            'source' => 'card_xxx',
        ], (array) $request->getModel());
    }

    public function testShouldRetrieveCustomerAndTokenAndReuseExistingCardOnPreCapture()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
                'customer' => ['id' => 'cus_xxx', 'foo' => 'fooVal', 'bar' => 'barVal'],
            ],
        ]);
        $request = new Capture($model);

        $gatewayMock = $this->createGatewayMock();

        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(RetrieveCustomer::class))
            ->willReturnCallback(function(RetrieveCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals([
                    'id' => 'cus_xxx',
                    'foo' => 'fooVal',
                    'bar' => 'barVal'
                ], (array) $model);

                $model['id'] = 'cus_xxx';
                $model['default_source'] = 'card_xxx';
                $model['sources'] = [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'card_xxx',
                            'fingerprint' => 'fingerprint_xxx',
                        ],
                        [
                            'id' => 'card_yyy',
                            'fingerprint' => 'fingerprint_yyy',
                        ],
                    ],
                ];
            });
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(RetrieveToken::class))
            ->willReturnCallback(function(RetrieveToken $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals([
                    'token' => 'tok_xxx',
                ], (array) $model);

                $model['id'] = 'tok_xxx';
                $model['card'] = [
                    'id' => 'card_xxx',
                    'fingerprint' => 'fingerprint_xxx',
                ];
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'customer' => 'cus_xxx',
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => 'cus_xxx',
                    'source' => 'tok_xxx',
                    'default_source' => 'card_xxx',
                    'foo' => 'fooVal',
                    'bar' => 'barVal',
                    'sources' => [
                        'object' => 'list',
                        'data' => [
                            [
                                'id' => 'card_xxx',
                                'fingerprint' => 'fingerprint_xxx',
                            ],
                            [
                                'id' => 'card_yyy',
                                'fingerprint' => 'fingerprint_yyy',
                            ],
                        ],
                    ],
                ],
                'card_id' => 'card_xxx',
            ],
            'source' => 'card_xxx',
        ], (array) $request->getModel());
    }

    public function testShouldRetrieveCustomerAndTokenAndCreateNewCardOnPreCapture()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
                'customer' => ['id' => 'cus_xxx', 'foo' => 'fooVal', 'bar' => 'barVal'],
            ],
        ]);
        $request = new Capture($model);

        $gatewayMock = $this->createGatewayMock();

        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(RetrieveCustomer::class))
            ->willReturnCallback(function(RetrieveCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals([
                    'id' => 'cus_xxx',
                    'foo' => 'fooVal',
                    'bar' => 'barVal'
                ], (array) $model);

                $model['id'] = 'cus_xxx';
                $model['default_source'] = 'card_xxx';
                $model['sources'] = [
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'card_xxx',
                            'fingerprint' => 'fingerprint_xxx',
                        ],
                        [
                            'id' => 'card_yyy',
                            'fingerprint' => 'fingerprint_yyy',
                        ],
                    ],
                ];
            });
        ;

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(RetrieveToken::class))
            ->willReturnCallback(function(RetrieveToken $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals([
                    'token' => 'tok_xxx',
                ], (array) $model);

                $model['id'] = 'tok_xxx';
                $model['card'] = [
                    'id' => 'card_zzz',
                    'fingerprint' => 'fingerprint_zzz',
                ];
            });
        ;

        $gatewayMock
            ->expects($this->at(2))
            ->method('execute')
            ->with($this->isInstanceOf(CreateCustomerSource::class))
            ->willReturnCallback(function(CreateCustomerSource $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals([
                    'customer' => 'cus_xxx',
                    'source' => 'tok_xxx',
                ], (array) $model);

                $model['id'] = 'card_zzz';
                $model['fingerprint'] = 'fingerprint_zzz';
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'customer' => 'cus_xxx',
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => 'cus_xxx',
                    'source' => 'tok_xxx',
                    'default_source' => 'card_xxx',
                    'foo' => 'fooVal',
                    'bar' => 'barVal',
                    'sources' => [
                        'object' => 'list',
                        'data' => [
                            [
                                'id' => 'card_xxx',
                                'fingerprint' => 'fingerprint_xxx',
                            ],
                            [
                                'id' => 'card_yyy',
                                'fingerprint' => 'fingerprint_yyy',
                            ],
                        ],
                    ],
                ],
                'card_id' => 'card_zzz',
            ],
            'source' => 'card_zzz',
        ], (array) $request->getModel());
    }

    public function testShouldSetStatusFailedIfCreateCustomerRequestFailedOnPreCapture()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
            ],
        ]);
        $request = new Capture($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(CreateCustomer::class))
            ->willReturnCallback(function(CreateCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals(['source' => 'tok_xxx'], (array) $model);

                // we assume the customer creation has failed when the customer does not have an id set.
                $model['id'] = null;
                $model['error'] = 'someError';
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'status' => Constants::STATUS_FAILED,
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => null,
                    'source' => 'tok_xxx',
                    'error' => 'someError'
                ],
                'card_id' => null,
            ],
            'error' => 'someError',
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfNotCaptureRequestOnPreExecute()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
            ],
        ]);
        $request = new Refund($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
            ],
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfSaveCardNotSetOnPreExecute()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
        ]);
        $request = new Capture($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'card' => 'tok_xxx',
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfSaveCardSetToFalseOnPreExecute()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => false,
            ],
        ]);
        $request = new Capture($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => false,
            ],
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfCardNotTokenOnPreExecute()
    {
        $model = new \ArrayObject([
            'card' => ['theTokenMustBeObtained'],
            'local' => [
                'save_card' => true,
            ],
        ]);
        $request = new Capture($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPreExecute($context);

        $this->assertEquals([
            'card' => ['theTokenMustBeObtained'],
            'local' => [
                'save_card' => true,
            ],
        ], (array) $request->getModel());
    }

    public function testShouldCreateCustomerAndReplaceCardTokenOnPostObtainToken()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => ['save_card' => true],
        ]);
        $request = new ObtainToken($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(CreateCustomer::class))
            ->willReturnCallback(function(CreateCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals(['source' => 'tok_xxx'], (array) $model);

                $model['id'] = 'theCustomerId';
                $model['default_source'] = 'card_xxx';
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'customer' => 'theCustomerId',
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => 'theCustomerId',
                    'default_source' => 'card_xxx',
                    'source' => 'tok_xxx',
                ],
                'card_id' => 'card_xxx',
            ],
            'source' => 'card_xxx',
        ], (array) $request->getModel());
    }

    public function testShouldCreateCustomerWithCustomInfoAndReplaceCardTokenOnPostObtainToken()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
                'customer' => ['foo' => 'fooVal', 'bar' => 'barVal'],
            ],
        ]);
        $request = new ObtainToken($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(CreateCustomer::class))
            ->willReturnCallback(function(CreateCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals([
                    'source' => 'tok_xxx',
                    'foo' => 'fooVal',
                    'bar' => 'barVal',
                ], (array) $model);

                $model['id'] = 'theCustomerId';
                $model['default_source'] = 'card_xxx';
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'customer' => 'theCustomerId',
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => 'theCustomerId',
                    'foo' => 'fooVal',
                    'bar' => 'barVal',
                    'default_source' => 'card_xxx',
                    'source' => 'tok_xxx',
                ],
                'card_id' => 'card_xxx',
            ],
            'source' => 'card_xxx',
        ], (array) $request->getModel());
    }

    public function testShouldSetStatusFailedIfCreateCustomerRequestFailedOnPostObtainToken()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
            ],
        ]);
        $request = new ObtainToken($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(CreateCustomer::class))
            ->willReturnCallback(function(CreateCustomer $request) {
                $model = $request->getModel();

                $this->assertInstanceOf(\ArrayObject::class, $model);

                $this->assertEquals(['source' => 'tok_xxx'], (array) $model);

                // we assume the customer creation has failed when the customer does not have an id set.
                $model['id'] = null;
                $model['error'] = 'someError';
            });
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'status' => Constants::STATUS_FAILED,
            'local' => [
                'save_card' => true,
                'customer' => [
                    'id' => null,
                    'source' => 'tok_xxx',
                    'error' => 'someError',
                ],
                'card_id' => null,
            ],
            'error' => 'someError',
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfNotCaptureRequestOnPostExecute()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
            ],
        ]);
        $request = new Refund($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => true,
            ],
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfSaveCardNotSetOnPostExecute()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
        ]);
        $request = new ObtainToken($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'card' => 'tok_xxx',
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfSaveCardSetToFalseOnPostExecute()
    {
        $model = new \ArrayObject([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => false,
            ],
        ]);
        $request = new ObtainToken($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'card' => 'tok_xxx',
            'local' => [
                'save_card' => false,
            ],
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfCardNotTokenOnPostExecute()
    {
        $model = new \ArrayObject([
            'card' => ['theTokenMustBeObtained'],
            'local' => [
                'save_card' => true,
            ],
        ]);
        $request = new ObtainToken($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'card' => ['theTokenMustBeObtained'],
            'local' => [
                'save_card' => true,
            ],
        ], (array) $request->getModel());
    }

    public function testShouldDoNothingIfCustomerSetOnObtainTokenPostExecute()
    {
        $model = new \ArrayObject([
            'customer' => 'aCustomerId',
            'card' => 'theTokenMustBeObtained',
            'local' => [
                'save_card' => true,
            ],
        ]);
        $request = new ObtainToken($model);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
        ;

        $context = new Context($gatewayMock, $request, []);

        $extension = new CreateCustomerExtension();
        $extension->onPostExecute($context);

        $this->assertEquals([
            'customer' => 'aCustomerId',
            'card' => 'theTokenMustBeObtained',
            'local' => [
                'save_card' => true,
            ],
        ], (array) $request->getModel());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->getMock(GatewayInterface::class);
    }
}