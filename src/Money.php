<?php

/**
 * This file is part of the Ulabox Money library.
 *
 * Copyright (c) 2011-2015 Ulabox SL
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Celtric\UbMoney;

class Money
{
    /**
     * The scale used in BCMath calculations
     */
    const SCALE = 4;

    /**
     * The money amount
     *
     * @var string
     */
    protected $amount;

    /**
     * The amount currency
     *
     * @var Currency
     */
    protected $currency;

    /**
     * @param string|int|float $amount Amount, expressed as a numeric value
     * @param Currency $currency
     *
     * @throws InvalidArgumentException If amount is not a numeric string value
     */
    public function __construct($amount, Currency $currency)
    {
        self::assertNumeric($amount);

        $this->amount = (string) $amount;
        $this->currency = $currency;
    }

    /**
     * Convenience factory method for a Money object
     *
     * <code>
     * $fiveDollar = Money::USD(500);
     * </code>
     *
     * @param string $method
     * @param array $arguments
     *
     * @return Money
     */
    public static function __callStatic($method, $arguments)
    {
        return new self($arguments[0], Currency::fromCode($method));
    }

    /**
     * Creates a Money object from its amount and currency
     *
     * @param string|int|float $amount A numeric value
     * @param Currency|string $currency
     *
     * @return Money
     */
    public static function fromAmount($amount, $currency)
    {
        if (!$currency instanceof Currency) {
            $currency = new Currency($currency);
        }

        return new self($amount, $currency);
    }

    /**
     * Returns a new Money instance based on the current one
     *
     * @param string $amount
     *
     * @return Money
     */
    private function newInstance($amount)
    {
        return new self($amount, $this->currency);
    }

    /**
     * Returns the value represented by this Money object
     *
     * @return string
     */
    public function amount()
    {
        return $this->amount;
    }

    /**
     * Returns the currency of this Money object
     *
     * @return Currency
     */
    public function currency()
    {
        return $this->currency;
    }

    /**
     * Returns a new Money object that represents
     * the sum of this and another Money object
     *
     * @param Money $addend
     *
     * @return Money
     */
    public function add(Money $addend)
    {
        $this->assertSameCurrencyAs($addend);

        $amount = bcadd($this->amount, $addend->amount, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Returns a new Money object that represents
     * the difference of this and another Money object
     *
     * @param Money $subtrahend
     *
     * @return Money
     */
    public function subtract(Money $subtrahend)
    {
        $this->assertSameCurrencyAs($subtrahend);

        $amount = bcsub($this->amount, $subtrahend->amount, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Returns a new Money object that represents
     * the multiplied value by the given factor
     *
     * @param string|int|float $multiplier A numeric value
     *
     * @return Money
     */
    public function multiplyBy($multiplier)
    {
        self::assertNumeric($multiplier);

        $amount = bcmul($this->amount, (string) $multiplier, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Returns a new Money object that represents
     * the divided value by the given factor
     *
     * @param string|int|float $divisor A numeric value
     *
     * @return Money
     */
    public function divideBy($divisor)
    {
        self::assertNumeric($divisor);

        $amount = bcdiv($this->amount, (string) $divisor, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Rounds this Money to another scale
     *
     * @param integer $scale
     *
     * @return Money
     */
    public function round($scale = 0)
    {
        if (!is_int($scale)) {
            throw new InvalidArgumentException('Scale is not an integer');
        }
        $add = '0.' . str_repeat('0', $scale) . '5';
        $newAmount = bcadd($this->amount, $add, $scale);

        return $this->newInstance($newAmount);
    }

    /**
     * Converts the currency of this Money object to
     * a given target currency with a given conversion rate
     *
     * @param Currency $targetCurrency
     * @param string|int|float $conversionRate A numeric value
     *
     * @return Money
     */
    public function convertTo(Currency $targetCurrency, $conversionRate)
    {
        self::assertNumeric($conversionRate);

        $amount = bcmul($this->amount, (string) $conversionRate, self::SCALE);

        return new Money($amount, $targetCurrency);
    }

    /**
     * Checks whether the value represented by this object equals to the other
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function equals(Money $other)
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * Checks whether the value represented by this object is greater than the other
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function isGreaterThan(Money $other)
    {
        return $this->compareTo($other) === 1;
    }

    /**
     * @param Money $other
     *
     * @return bool
     */
    public function isGreaterThanOrEqualTo(Money $other)
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * Checks whether the value represented by this object is less than the other
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function isLessThan(Money $other)
    {
        return $this->compareTo($other) === -1;
    }

    /**
     * @param Money $other
     *
     * @return bool
     */
    public function isLessThanOrEqualTo(Money $other)
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * Checks if the value represented by this object is zero
     *
     * @return boolean
     */
    public function isZero()
    {
        return $this->compareTo0() === 0;
    }

    /**
     * Checks if the value represented by this object is positive
     *
     * @return boolean
     */
    public function isPositive()
    {
        return $this->compareTo0() === 1;
    }

    /**
     * Checks if the value represented by this object is negative
     *
     * @return boolean
     */
    public function isNegative()
    {
        return $this->compareTo0() === -1;
    }

    /**
     * Checks whether a Money has the same Currency as this
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function hasSameCurrencyAs(Money $other)
    {
        return $this->currency->equals($other->currency);
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than the other
     *
     * @param Money $other
     *
     * @return int
     */
    private function compareTo(Money $other)
    {
        $this->assertSameCurrencyAs($other);

        return bccomp($this->amount, $other->amount);
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than 0
     *
     * @return int
     */
    private function compareTo0()
    {
        return bccomp($this->amount, '');
    }

    /**
     * Asserts that a Money has the same currency as this
     *
     * @param Money $other
     *
     * @throws InvalidArgumentException If $other has a different currency
     */
    private function assertSameCurrencyAs(Money $other)
    {
        if (!$this->hasSameCurrencyAs($other)) {
            throw new InvalidArgumentException('Currencies must be identical');
        }
    }

    /**
     * Asserts that a value is a valid numeric string
     *
     * @param string|int|float $value A numeric value
     *
     * @throws InvalidArgumentException If $other has a different currency
     */
    private static function assertNumeric($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                'Amount must be a valid numeric value'
            );
        }
    }
}
