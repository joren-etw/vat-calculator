<?php

namespace Tests;

use Mockery as m;
use Mpociot\VatCalculator\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    public static $file_get_contents_result;

    protected function tearDown(): void
    {
        m::close();
    }

    public function testCalculateVatWithoutCountry()
    {
        $config = m::mock('Illuminate\Contracts\Config\Repository');

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.')
            ->andReturn(false);

        $net = 25.00;

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net);
        $this->assertEquals(25.00, $result);
    }

    public function testCalculateVatWithoutCountryAndConfig()
    {
        $net = 25.00;

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculate($net);
        $this->assertEquals(25.00, $result);
    }

    public function testCalculateVatWithPredefinedRules()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.DE')
            ->andReturn(false);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculateVatWithPredefinedRulesWithoutConfig()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculateVatWithPredefinedRulesOverwrittenByConfiguration()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $taxKey = 'vat_calculator.rules.'.strtoupper($countryCode);

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with($taxKey, 0)
            ->andReturn(0.50);

        $config->shouldReceive('has')
            ->once()
            ->with($taxKey)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(36.00, $result);
        $this->assertEquals(0.50, $vatCalculator->getTaxRate());
        $this->assertEquals(12.00, $vatCalculator->getTaxValue());
    }

    public function testCalculateVatWithPredefinedRulesOverwrittenByArrayAsConfiguration()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $taxKey = 'vat_calculator.rules.'.strtoupper($countryCode);

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->times(3)
            ->with($taxKey, 0)
            ->andReturn(
            ['rate'   => 0.19,
                'rates' => [
                    'high' => 0.50,
                    'low' => 0.07,
                ],
            ]);

        $config->shouldReceive('has')
            ->times(3)
            ->with($taxKey)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);

        $result = $vatCalculator->calculate($net, $countryCode, null, null, 'high');
        $this->assertEquals(36.00, $result);
        $this->assertEquals(0.50, $vatCalculator->getTaxRate());
        $this->assertEquals(12.00, $vatCalculator->getTaxValue());

        $result = $vatCalculator->calculate($net, $countryCode, null, null, 'low');
        $this->assertEquals(25.68, $result);
        $this->assertEquals(0.07, $vatCalculator->getTaxRate());
        $this->assertEquals(1.68, $vatCalculator->getTaxValue());

        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculatVatWithCountryDirectSet()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode, 0)
            ->andReturn(0.19);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculatVatWithCountryDirectSetWithoutConfiguration()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculatVatWithCountryPreviousSet()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode, 0)
            ->andReturn(0.19);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);

        $result = $vatCalculator->calculate($net);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculatVatWithCountryAndCompany()
    {
        $net = 24.00;
        $countryCode = 'DE';
        $postalCode = null;
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode, $postalCode, $company);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function testCalculatVatWithCountryAndCompanySet()
    {
        $net = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculate($net, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(24.00, $vatCalculator->getNetPrice());
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function testCalculatVatWithCountryAndCompanyBothSet()
    {
        $net = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculate($net);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function testGetTaxRateForLocationWithCountry()
    {
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode, 0)
            ->andReturn(0.19);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForLocation($countryCode);
        $this->assertEquals(0.19, $result);
    }

    public function testGetTaxRateForCountry()
    {
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode, 0)
            ->andReturn(0.19);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForCountry($countryCode);
        $this->assertEquals(0.19, $result);
    }

    public function testGetTaxRateForLocationWithCountryAndCompany()
    {
        $countryCode = 'DE';
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForLocation($countryCode, null, $company);
        $this->assertEquals(0, $result);
    }

    public function testGetTaxRateForCountryAndCompany()
    {
        $countryCode = 'DE';
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->getTaxRateForCountry($countryCode, $company);
        $this->assertEquals(0, $result);
    }

    public function testCanValidateValidVATNumber()
    {
        $config = m::mock('Illuminate\Contracts\Config\Repository');

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $result = new \stdClass();
        $result->valid = true;

        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'DE',
                'vatNumber'   => '190098891',
            ])
            ->willReturn($result);

        $vatNumber = 'DE 190 098 891';
        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertTrue($result);
    }

    public function testCanValidateInvalidVATNumber()
    {
        $result = new \stdClass();
        $result->valid = false;

        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber'   => 'meInvalidNumber',
            ])
            ->willReturn($result);

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator();
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertFalse($result);
    }

    public function testValidateVATNumberReturnsFalseOnSoapFailure()
    {
        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber'   => 'meInvalidNumber',
            ])
            ->willThrowException(new \SoapFault('Server', 'Something went wrong'));

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator();
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertFalse($result);
    }

    public function testValidateVATNumberReturnsFalseOnSoapFailureWithoutForwarding()
    {
        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber'   => 'meInvalidNumber',
            ])
            ->willThrowException(new \SoapFault('Server', 'Something went wrong'));

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.forward_soap_faults')
            ->andReturn(false);

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setSoapClient($vatCheck);
        $result = $vatCalculator->isValidVATNumber($vatNumber);
        $this->assertFalse($result);
    }

    public function testValidateVATNumberThrowsExceptionOnSoapFailure()
    {
        $this->expectException(\Mpociot\VatCalculator\Exceptions\VATCheckUnavailableException::class);

        $vatCheck = $this->getMockFromWsdl(__DIR__.'/checkVatService.wsdl', 'VATService');
        $vatCheck->expects($this->any())
            ->method('checkVat')
            ->with([
                'countryCode' => 'So',
                'vatNumber'   => 'meInvalidNumber',
            ])
            ->willThrowException(new \SoapFault('Server', 'Something went wrong'));

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.forward_soap_faults')
            ->andReturn(true);

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setSoapClient($vatCheck);
        $vatCalculator->isValidVATNumber($vatNumber);
    }

    public function testCannotValidateVATNumberWhenServiceIsDown()
    {
        $this->expectException(\Mpociot\VatCalculator\Exceptions\VATCheckUnavailableException::class);

        $result = new \stdClass();
        $result->valid = false;

        $vatNumber = 'SomeInvalidNumber';
        $vatCalculator = new VatCalculator();
        $vatCalculator->setSoapClient(false);
        $vatCalculator->isValidVATNumber($vatNumber);
    }

    /**
     * @link https://tools.tracemyip.org/search--country/germany
     */
    public function testCanResolveIPToCountry()
    {
        $_SERVER['REMOTE_ADDR'] = '84.171.73.5'; // Deutsche Telekom AG

        $vatCalculator = new VatCalculator();
        $country = $vatCalculator->getIPBasedCountry();
        $this->assertEquals('DE', $country);
    }

    /**
     * @link https://tools.tracemyip.org/search--country/germany
     */
    public function testCanResolveIP6ToCountry()
    {
        $_SERVER['REMOTE_ADDR'] = '2001:16b8:1866:b500:c07:d1eb:b865:6d35'; // Versatel Deutschland

        $vatCalculator = new VatCalculator();
        $country = $vatCalculator->getIPBasedCountry();
        $this->assertEquals('DE', $country);
    }

    public function testCanResolveInvalidIPToCountry()
    {
        $_SERVER['REMOTE_ADDR'] = '';

        $vatCalculator = new VatCalculator();
        $country = $vatCalculator->getIPBasedCountry();
        $this->assertFalse($country);
    }

    public function testCanHandleIPServiceDowntime()
    {
        $_SERVER['REMOTE_ADDR'] = '';

        $vatCalculator = new VatCalculator();
        $country = $vatCalculator->getIPBasedCountry();
        $this->assertFalse($country);
    }

    public function testCompanyInBusinessCountryGetsValidVATRate()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.DE')
            ->andReturn(false);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(true);

        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.business_country_code', '')
            ->andReturn($countryCode);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCompanyInBusinessCountryGetsValidVATRateDirectSet()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator();
        $vatCalculator->setBusinessCountryCode('DE');
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCompanyOutsideBusinessCountryGetsValidVATRate()
    {
        $net = 24.00;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator();
        $vatCalculator->setBusinessCountryCode('NL');
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());
        $this->assertEquals(0.00, $vatCalculator->getTaxValue());
    }

    public function testReturnsZeroForInvalidCountryCode()
    {
        $net = 24.00;
        $countryCode = 'XXX';

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculate($net, $countryCode, null, true);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());
        $this->assertEquals(0.00, $vatCalculator->getTaxValue());
    }

    public function testChecksPostalCodeForVATExceptions()
    {
        $net = 24.00;
        $vatCalculator = new VatCalculator();
        $postalCode = '27498'; // Heligoland
        $result = $vatCalculator->calculate($net, 'DE', $postalCode, false);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.00, $vatCalculator->getTaxRate());
        $this->assertEquals(0.00, $vatCalculator->getTaxValue());

        $postalCode = '6691'; // Jungholz
        $result = $vatCalculator->calculate($net, 'AT', $postalCode, false);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());

        $postalCode = 'BFPO58'; // Dhekelia
        $result = $vatCalculator->calculate($net, 'GB', $postalCode, false);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());

        $postalCode = '9122'; // Madeira
        $result = $vatCalculator->calculate($net, 'PT', $postalCode, false);
        $this->assertEquals(29.28, $result);
        $this->assertEquals(0.22, $vatCalculator->getTaxRate());
        $this->assertEquals(5.28, $vatCalculator->getTaxValue());
    }

    public function testPostalCodesWithoutExceptionsGetStandardRate()
    {
        $net = 24.00;
        $vatCalculator = new VatCalculator();

        // Invalid post code
        $postalCode = 'IGHJ987ERT35';
        $result = $vatCalculator->calculate($net, 'ES', $postalCode, false);
        //Expect standard rate for Spain
        $this->assertEquals(29.04, $result);
        $this->assertEquals(0.21, $vatCalculator->getTaxRate());
        $this->assertEquals(5.04, $vatCalculator->getTaxValue());

        // Valid UK post code
        $postalCode = 'S1A 2AA';
        $result = $vatCalculator->calculate($net, 'GB', $postalCode, false);
        //Expect standard rate for UK
        $this->assertEquals(28.80, $result);
        $this->assertEquals(0.20, $vatCalculator->getTaxRate());
        $this->assertEquals(4.80, $vatCalculator->getTaxValue());
    }

    public function testShouldCollectVAT()
    {
        $vatCalculator = new VatCalculator();
        $this->assertTrue($vatCalculator->shouldCollectVAT('DE'));
        $this->assertTrue($vatCalculator->shouldCollectVAT('NL'));
        $this->assertFalse($vatCalculator->shouldCollectVAT(''));
        $this->assertFalse($vatCalculator->shouldCollectVAT('XXX'));
    }

    public function testShouldCollectVATFromConfig()
    {
        $countryCode = 'TEST';
        $taxKey = 'vat_calculator.rules.'.strtoupper($countryCode);

        $config = m::mock('Illuminate\Contracts\Config\Repository');

        $config->shouldReceive('has')
            ->with($taxKey)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $this->assertTrue($vatCalculator->shouldCollectVAT($countryCode));
    }

    public function testCalculateNetPriceWithoutCountry()
    {
        $config = m::mock('Illuminate\Contracts\Config\Repository');

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.')
            ->andReturn(false);

        $gross = 25.00;

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(25.00, $result);
    }

    public function testCalculateNetPriceWithoutCountryAndConfig()
    {
        $gross = 25.00;

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(25.00, $result);
    }

    public function testCalculateNetPriceWithPredefinedRules()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.DE')
            ->andReturn(false);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithPredefinedRulesWithoutConfig()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithPredefinedRulesOverwrittenByConfiguration()
    {
        $gross = 36.00;
        $countryCode = 'DE';

        $taxKey = 'vat_calculator.rules.'.strtoupper($countryCode);

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with($taxKey, 0)
            ->andReturn(0.50);

        $config->shouldReceive('has')
            ->once()
            ->with($taxKey)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.50, $vatCalculator->getTaxRate());
        $this->assertEquals(12.00, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithCountryDirectSet()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode, 0)
            ->andReturn(0.19);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithCountryDirectSetWithoutConfiguration()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $vatCalculator = new VatCalculator();

        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithCountryPreviousSet()
    {
        $gross = 28.56;
        $countryCode = 'DE';

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode, 0)
            ->andReturn(0.19);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.rules.'.$countryCode)
            ->andReturn(true);

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);

        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0.19, $vatCalculator->getTaxRate());
        $this->assertEquals(4.56, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithCountryAndCompany()
    {
        $gross = 28.56;
        $countryCode = 'DE';
        $postalCode = null;
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $result = $vatCalculator->calculateNet($gross, $countryCode, $postalCode, $company);
        $this->assertEquals(28.56, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithCountryAndCompanySet()
    {
        $gross = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculateNet($gross, $countryCode);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(24.00, $vatCalculator->getNetPrice());
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function testCalculateNetPriceWithCountryAndCompanyBothSet()
    {
        $gross = 24.00;
        $countryCode = 'DE';
        $company = true;

        $config = m::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')
            ->never();

        $config->shouldReceive('has')
            ->once()
            ->with('vat_calculator.business_country_code')
            ->andReturn(false);

        $vatCalculator = new VatCalculator($config);
        $vatCalculator->setCountryCode($countryCode);
        $vatCalculator->setCompany($company);
        $result = $vatCalculator->calculateNet($gross);
        $this->assertEquals(24.00, $result);
        $this->assertEquals(0, $vatCalculator->getTaxRate());
        $this->assertEquals(0, $vatCalculator->getTaxValue());
    }

    public function testCalculateHighVatType()
    {
        $gross = 24.00;
        $countryCode = 'NL';
        $company = false;
        $type = 'high';
        $postalCode = null;

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculate($gross, $countryCode, $postalCode, $company, $type);

        $this->assertEquals(29.04, $result);
    }

    public function testCalculateLowVatType()
    {
        $gross = 24.00;
        $countryCode = 'NL';
        $company = false;
        $type = 'low';
        $postalCode = null;

        $vatCalculator = new VatCalculator();
        $result = $vatCalculator->calculate($gross, $countryCode, $postalCode, $company, $type);

        $this->assertEquals(26.16, $result);
    }
}
