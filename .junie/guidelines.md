# GravityView Development Guidelines

## Project Structure

### Core Architecture
- **WordPress Plugin** with Gravity Forms integration
- **PHP 7.4+** with modern OOP patterns
- **Search System** built with abstract classes and inheritance
- **Test-Driven Development** using PHPUnit and Codeception

### Directory Organization
```text
/includes/
  /search/
    /fields/         # Search field implementations
    abstract-*.php   # Abstract base classes
/tests/
  bootstrap.php      # Test environment setup
  *_Test.php        # PHPUnit test files
/assets/
  /js/              # JavaScript (Grunt build process)
  /css/             # Sass compiled stylesheets
```

## Development Environment

### Prerequisites
- PHP 7.4+
- WordPress test environment
- Gravity Forms plugin
- Node.js (npm for frontend assets)

### Setup Commands
```bash
# Install dependencies
composer install
npm install

# Set up test environment
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
export GF_PLUGIN_DIR=/path/to/gravityforms

# Run tests
vendor/bin/phpunit
```

### Build Process
```bash
# Frontend development
npm run dev          # Development build
npm run build        # Production build
grunt watch          # Watch for changes

# Code quality
composer run lint    # PHP linting
npm run lint         # JavaScript linting
```

## Testing Standards

### PHPUnit Test Structure
```php
final class MyClass_Test extends TestCase {
	private MyClass $subject;

	protected function setUp(): void {
		parent::setUp();
		$this->subject = new MyClass();
	}

	public function test_method_behavior(): void {
		// Arrange, Act, Assert pattern
		self::assertSame( 'expected', $this->subject->method() );
	}
}
```

### Test Organization
- **Unit Tests**: `TestCase` for isolated component testing
- **Integration Tests**: `GV_UnitTestCase` for WordPress/GF integration
- **End-to-End**: Playwright tests in `/tests/e2e/`

### Required Test Coverage
- All public methods
- Configuration handling (`from_configuration`, `to_configuration`)
- Template data generation (`to_template_data`)
- Request value handling (`has_request_value`)

## Code Standards

### PHP Guidelines
- **PSR-4 Autoloading** with namespace `GV\Search\Fields`
- **Type Declarations** required for all parameters/returns
- **Final Classes** for concrete implementations
- **Abstract Classes** for shared behavior

### Search Field Development
```php
final class Search_Field_Custom extends Search_Field {
	protected string $icon = 'dashicons-icon';
	protected static string $type = 'custom';
	protected static string $field_type = 'text';

	protected function get_name(): string {
		return 'Custom Field';
	}

	public function get_description(): string {
		return 'Field description.';
	}
}
```

### Documentation Requirements
- **DocBlocks** for all classes and methods
- **@since $ver$** tags for version tracking
- **Comments ending with periods** for consistency

## Git Workflow

### Branch Naming
- `feature/search-field-name` - New features
- `fix/issue-description` - Bug fixes
- `test/component-name` - Test additions

### Commit Guidelines
- Descriptive messages with context
- Reference issue numbers when applicable
- Small, focused commits

## Frontend Development

### JavaScript
- **ES6+** syntax with Babel transpilation
- **Grunt** for task automation
- **ESLint** for code quality

### CSS/Sass
- **Sass** preprocessing with node-sass
- **PostCSS** with autoprefixer
- **Component-based** organization

### Build Commands
```bash
grunt sass           # Compile Sass
grunt uglify         # Minify JavaScript
grunt newer:imagemin # Optimize images
grunt potomo         # Generate translations
```

## Performance Considerations

### PHP Optimization
- Lazy loading for heavy operations
- Caching for repeated calculations
- Minimal database queries

### Frontend Optimization
- Minified assets in production
- Image optimization
- Progressive enhancement

## Common Patterns

### Factory Pattern
```php
$field = Search_Field::from_configuration( $config );
```

### Template Data Generation
```php
public function to_template_data(): array {
	return array_merge( parent::to_template_data(), [
		'custom_property' => $this->get_custom_value(),
	] );
}
```

### Request Handling
```php
protected function get_input_value() {
	return $this->get_request_value( 'param_name', '' );
}
```

## Debugging

### Test Debugging
```bash
# Verbose test output with debug info
phpunit --debug --verbose
```

### WordPress Integration
- Use `error_log()` for debugging
- Enable WP_DEBUG in test environment
- Leverage WordPress hooks for integration points

## Resources

- **PHPUnit Documentation**: [phpunit.de](https://phpunit.de)
- **WordPress Coding Standards**: [developer.wordpress.org](https://developer.wordpress.org/coding-standards/)
- **Gravity Forms API**: [docs.gravityforms.com](https://docs.gravityforms.com)
# GravityView Development Guidelines

## Project Structure

### Core Architecture
- **WordPress Plugin** with Gravity Forms integration
- **PHP 7.4+** with modern OOP patterns
- **Search System** built with abstract classes and inheritance
- **Test-Driven Development** using PHPUnit and Codeception

### Directory Organization
```text
/includes/
  /search/
    /fields/         # Search field implementations
    abstract-*.php   # Abstract base classes
/tests/
  bootstrap.php      # Test environment setup
  *_Test.php        # PHPUnit test files
/assets/
  /js/              # JavaScript (Grunt build process)
  /css/             # Sass compiled stylesheets
```

## Development Environment

### Prerequisites
- PHP 7.4+
- WordPress test environment
- Gravity Forms plugin
- Node.js (npm for frontend assets)

### Setup Commands
```bash
# Install dependencies
composer install
npm install

# Set up test environment
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
export GF_PLUGIN_DIR=/path/to/gravityforms

# Run tests
vendor/bin/phpunit
```

### Build Process
```bash
# Frontend development
npm run dev          # Development build
npm run build        # Production build
grunt watch          # Watch for changes

# Code quality
composer run lint    # PHP linting
npm run lint         # JavaScript linting
```

## Testing Standards

### PHPUnit Test Structure
```php
final class MyClass_Test extends TestCase {
	private MyClass $subject;

	protected function setUp(): void {
		parent::setUp();
		$this->subject = new MyClass();
	}

	public function test_method_behavior(): void {
		// Arrange, Act, Assert pattern
		self::assertSame( 'expected', $this->subject->method() );
	}
}
```

### Test Organization
- **Unit Tests**: `TestCase` for isolated component testing
- **Integration Tests**: `GV_UnitTestCase` for WordPress/GF integration
- **End-to-End**: Playwright tests in `/tests/e2e/`

### Required Test Coverage
- All public methods
- Configuration handling (`from_configuration`, `to_configuration`)
- Template data generation (`to_template_data`)
- Request value handling (`has_request_value`)
- Do not directly test `protected` or `public` methods
- Don't create Mocks, but use an in-memory or anonymous class instead

## Code Standards

### PHP Guidelines
- **PSR-4 Autoloading** with namespace `GV\Search\Fields`
- **Type Declarations** required for all parameters/returns
- **Final Classes** for concrete implementations
- **Abstract Classes** for shared behavior

### Search Field Development
```php
final class Search_Field_Custom extends Search_Field {
	protected string $icon = 'dashicons-icon';
	protected static string $type = 'custom';
	protected static string $field_type = 'text';

	protected function get_name(): string {
		return 'Custom Field';
	}

	public function get_description(): string {
		return 'Field description.';
	}
}
```

### Documentation Requirements
- **DocBlocks** for all classes and methods
- **@since $ver$** tags for version tracking
- **Comments ending with periods** for consistency

## Git Workflow

### Branch Naming
- `feature/search-field-name` - New features
- `fix/issue-description` - Bug fixes
- `test/component-name` - Test additions

### Commit Guidelines
- Descriptive messages with context
- Reference issue numbers when applicable
- Small, focused commits

## Frontend Development

### JavaScript
- **ES6+** syntax with Babel transpilation
- **Grunt** for task automation
- **ESLint** for code quality

### CSS/Sass
- **Sass** preprocessing with node-sass
- **PostCSS** with autoprefixer
- **Component-based** organization

### Build Commands
```bash
grunt sass           # Compile Sass
grunt uglify         # Minify JavaScript
grunt newer:imagemin # Optimize images
grunt potomo         # Generate translations
```

## Performance Considerations

### PHP Optimization
- Lazy loading for heavy operations
- Caching for repeated calculations
- Minimal database queries

### Frontend Optimization
- Minified assets in production
- Image optimization
- Progressive enhancement

## Common Patterns

### Factory Pattern
```php
$field = Search_Field::from_configuration( $config );
```

### Template Data Generation
```php
public function to_template_data(): array {
	return array_merge( parent::to_template_data(), [
		'custom_property' => $this->get_custom_value(),
	] );
}
```

### Request Handling
```php
protected function get_input_value() {
	return $this->get_request_value( 'param_name', '' );
}
```

## Debugging

### Test Debugging
```bash
# Verbose test output with debug info
phpunit --debug --verbose
```

### WordPress Integration
- Use `error_log()` for debugging
- Enable WP_DEBUG in test environment
- Leverage WordPress hooks for integration points

## Resources

- **PHPUnit Documentation**: [phpunit.de](https://phpunit.de)
- **WordPress Coding Standards**: [developer.wordpress.org](https://developer.wordpress.org/coding-standards/)
- **Gravity Forms API**: [docs.gravityforms.com](https://docs.gravityforms.com)
