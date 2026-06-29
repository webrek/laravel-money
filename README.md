# Laravel Money

[![Última versión en Packagist](https://img.shields.io/packagist/v/webrek/laravel-money.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-money)
[![Descargas totales](https://img.shields.io/packagist/dt/webrek/laravel-money.svg?style=flat-square)](https://packagist.org/packages/webrek/laravel-money)
[![Pruebas](https://img.shields.io/github/actions/workflow/status/webrek/laravel-money/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/webrek/laravel-money/actions/workflows/tests.yml)
[![Versión de PHP](https://img.shields.io/packagist/php-v/webrek/laravel-money.svg?style=flat-square)](https://php.net)
[![Licencia](https://img.shields.io/packagist/l/webrek/laravel-money.svg?style=flat-square)](LICENSE)

Un objeto de valor inmutable para representar dinero en Laravel. Los montos se
almacenan como un entero de unidades menores (centavos), la aritmética es exacta
y el redondeo solo ocurre donde tú lo pides explícitamente.

## Inicio rápido

```bash
composer require webrek/laravel-money
```

```php
use Webrek\Money\Money;

$price = Money::of('19.99', 'USD');     // desde unidades mayores
$tax   = $price->times('0.16');          // 16% — redondeado HALF_UP a 3.20

$total = $price->plus($tax);             // USD 23.19
$total->format();                        // "USD 23.19"
$total->minorAmount;                     // 2319
```

## ¿Por qué no usar simplemente una columna decimal y flotantes?

Porque el dinero y los flotantes binarios no se llevan. `0.1 + 0.2` no es `0.3`,
y un centavo que se desvía dentro de un bucle se convierte en un ticket de
conciliación. El enfoque robusto —usado por todo sistema de pagos serio— es
almacenar el dinero como un conteo entero de la unidad más pequeña (centavos,
fils, yenes) y nunca dejar que un flotante se acerque.

Este paquete te da eso como un tipo de primera clase:

- **Exacto con enteros.** Cada monto es un `int` de unidades menores más una
  `Currency`. La suma y la resta no pueden perder precisión porque no hay
  precisión que perder.
- **El redondeo es explícito.** Las únicas operaciones que pueden producir una
  fracción de centavo —la multiplicación y la división— reciben un
  `RoundingMode`, con `HALF_UP` por defecto. Nada se redondea a tus espaldas.
- **No se crea ni se destruye dinero al repartir.** `allocate()` y `split()`
  distribuyen hasta la última unidad menor.
- **Consciente de la moneda.** Las operaciones entre monedas lanzan una excepción
  en lugar de producir resultados sin sentido en silencio, y cada moneda conoce
  su propia escala (USD tiene 2 decimales, JPY tiene 0, BHD tiene 3).

No depende de `moneyphp/money` — es un tipo enfocado y nativo de Laravel.

## Construir dinero

```php
Money::of('10.99', 'USD');     // unidades mayores (el string es lo más seguro)
Money::of(10.99, 'USD');       // se acepta float, redondeado a la escala de la moneda
Money::ofMinor(1099, 'USD');   // unidades menores directamente
Money::zero('USD');

// La escala de la moneda se respeta automáticamente:
Money::of('1000', 'JPY')->minorAmount;   // 1000  (JPY no tiene unidad menor)
Money::of('1.234', 'BHD')->minorAmount;  // 1234  (BHD tiene 3)
```

## Aritmética

```php
$a = Money::of('10.00', 'USD');
$b = Money::of('2.50', 'USD');

$a->plus($b);              // 12.50
$a->minus($b);            // 7.50
$a->times(3);             // 30.00
$a->dividedBy(3);         // 3.33  (HALF_UP)
$a->negated();            // -10.00
$a->abs();

$a->plus(Money::of('1', 'EUR'));   // lanza CurrencyMismatchException
```

### Modos de redondeo

`times()` y `dividedBy()` aceptan cualquier [`RoundingMode`](src/RoundingMode.php):
`UP`, `DOWN`, `CEILING`, `FLOOR`, `HALF_UP` (por defecto), `HALF_DOWN`, `HALF_EVEN`
(redondeo bancario).

```php
use Webrek\Money\RoundingMode;

Money::ofMinor(1099, 'USD')->times('1.5', RoundingMode::DOWN); // 16.48
Money::ofMinor(1099, 'USD')->times('1.5', RoundingMode::UP);   // 16.49
```

## Conversión de moneda

Convierte con una tasa explícita (cuántas unidades de la moneda destino equivalen
a una unidad de la moneda origen), con la escala y el redondeo resueltos por ti:

```php
Money::of('10.00', 'USD')->convertTo('EUR', '0.92');   // EUR 9.20
Money::of('10.00', 'USD')->convertTo('JPY', '150');    // JPY 1500  (0 decimales)
Money::of('1000', 'JPY')->convertTo('USD', '0.0067');  // USD 6.70
```

O resuelve la tasa desde un `ExchangeRateProvider`. El `ArrayExchangeRateProvider`
incluido recibe un mapa de moneda => tasa relativa a una base común y calcula las
tasas cruzadas por ti:

```php
use Webrek\Money\ArrayExchangeRateProvider;

$rates = new ArrayExchangeRateProvider(['USD' => 1, 'EUR' => 0.92, 'MXN' => 17.5]);

Money::of('100', 'USD')->convert('EUR', $rates);   // EUR 92.00
Money::of('175', 'MXN')->convert('USD', $rates);   // USD 10.00  (tasa cruzada)
```

Configura el proveedor por defecto en `config/money.php` y resuélvelo desde el
contenedor:

```php
'exchange' => ['rates' => ['USD' => 1, 'EUR' => 0.92, 'MXN' => 17.5]],
```

```php
use Webrek\Money\Contracts\ExchangeRateProvider;

$eur = $price->convert('EUR', app(ExchangeRateProvider::class));
```

Conecta tasas en vivo implementando tú mismo `ExchangeRateProvider` (por ejemplo,
respaldado por una API y caché) y vinculándolo al contrato.

## Repartir sin perder centavos

```php
// Divide una cuenta en tres — el centavo sobrante se reparte, nada se pierde.
$shares = Money::ofMinor(100, 'USD')->split(3);
// [USD 0.34, USD 0.33, USD 0.33]   (suma de vuelta exactamente 1.00)

// Reparte por proporción (por ejemplo, un reparto de ingresos 70/30):
Money::ofMinor(100, 'USD')->allocate(7, 3);
// [USD 0.70, USD 0.30]
```

El residuo se entrega primero a las proporciones más grandes, de modo que los
repartos son estables y justos, y `array_sum` de las partes siempre es igual al
original.

## Agregados y porcentajes

```php
Money::sum([$a, $b, $c]);   // total (todas la misma moneda)
Money::min([$a, $b, $c]);
Money::max([$a, $b, $c]);

$price->percentage(16);      // 16% — por ejemplo, impuesto
$price->percentage('8.25');  // las tasas fraccionarias son bienvenidas
```

Suma una colección directamente, opcionalmente por clave:

```php
$orders->sumMoney('total');      // Money|null
collect([$a, $b])->sumMoney();   // Money|null
```

`sumMoney()` devuelve `null` para una colección vacía; `Money::sum()` lanza una
excepción con un conjunto vacío (no hay moneda que devolver).

## Comparación

```php
$a->isEqualTo($b);
$a->isGreaterThan($b);
$a->isGreaterThanOrEqualTo($b);
$a->isLessThan($b);
$a->isLessThanOrEqualTo($b);
$a->compareTo($b);        // -1 | 0 | 1
$a->isZero();
$a->isPositive();
$a->isNegative();
```

## Casting con Eloquent

Almacena las unidades menores en una columna entera y conviértela (cast) a `Money`.

**Moneda única** — la columna contiene las unidades menores; la moneda es fija en
el cast:

```php
use Webrek\Money\Casts\MoneyCast;

protected function casts(): array
{
    return ['price' => MoneyCast::class . ':USD'];
}
```

```php
$product->price = Money::of('19.99', 'USD');
$product->save();                 // almacena 1999 en `price`
$product->price->format();        // "USD 19.99"
```

**Multimoneda** — agrega una columna string acompañante `{column}_currency` y
omite el código:

```php
protected function casts(): array
{
    return ['cost' => MoneyCast::class];   // lee/escribe `cost` y `cost_currency`
}
```

```php
$product->cost = Money::of('15.50', 'EUR');   // almacena 1550 + "EUR"
```

Asignar una moneda que no coincide con una columna de moneda fija lanza una
`CurrencyMismatchException`.

## Validación

```php
use Webrek\Money\Rules\CurrencyCode;

$request->validate([
    'currency' => ['required', new CurrencyCode],
]);
```

## Formato y serialización

```php
$money = Money::ofMinor(123456, 'USD');

$money->format();             // "USD 1,234.56"   (independiente del locale)
$money->formatTo('en_US');    // "$1,234.56"      (requiere ext-intl)
$money->toDecimal();          // "1234.56"
(string) $money;              // "1234.56 USD"

json_encode($money);
// {"amount":"1234.56","minorAmount":123456,"currency":"USD"}
```

`formatTo()` usa la extensión intl; sin ella, recae en `format()`.

## Requisitos

| Componente | Versión |
| --------- | ------- |
| PHP | 8.2+ |
| Laravel | 12.x / 13.x |
| ext-intl | opcional, para `formatTo()` |
| ext-bcmath | opcional, para multiplicación/división exacta a gran escala |

## Pruebas

```bash
composer install
composer test
```

## Contribuir

Consulta [CONTRIBUTING.md](CONTRIBUTING.md).

## Seguridad

Por favor revisa la [política de seguridad](SECURITY.md) antes de reportar una
vulnerabilidad.

## Licencia

La Licencia MIT (MIT). Consulta [LICENSE](LICENSE).
