Create a new service following the project's architecture. The service name and purpose: $ARGUMENTS

Execute these steps in order. Do not skip any.

## Step 1 — Interface in `src/Contract/`

Create `src/Contract/{Name}Interface.php`:
- `declare(strict_types=1)`
- `namespace App\Contract`
- Define only the public methods the service exposes
- No implementation, no properties

## Step 2 — Service in `src/Service/`

Create `src/Service/{Name}.php`:
- `declare(strict_types=1)`
- `namespace App\Service`
- `implements {Name}Interface`
- Constructor injection only — never `new` for injected dependencies
- Use `readonly` properties for injected services
- Return type declarations on all methods
- Prefer early returns over nested conditionals

## Step 3 — DI binding in `config/services.yaml`

Add under `services:`:
```yaml
App\Contract\{Name}Interface:
    class: App\Service\{Name}
```

## Step 4 — Unit test in `tests/Unit/Service/`

Create `tests/Unit/Service/{Name}Test.php`:
- `declare(strict_types=1)`
- `namespace App\Tests\Unit\Service`
- Extends `PHPUnit\Framework\TestCase`
- Mock interfaces, never concrete classes
- Test the public methods defined in the interface
- At minimum: one test per public method

## Verification

After all 4 files are created, confirm:
- [ ] Interface exists in `src/Contract/`
- [ ] Service `implements` the interface
- [ ] DI binding added to `config/services.yaml`
- [ ] Unit test file exists in `tests/Unit/Service/`
