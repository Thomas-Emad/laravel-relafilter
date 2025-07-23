# Laravel Relafilter

**Laravel Relafilter** is a lightweight and expressive filtering package for Laravel models. It allows you to filter using deeply nested relationships without writing complex query logic.

> ⚠️ This package is **not a replacement** for Laravel's Query Builder. Instead, it's a **lightweight and simpler alternative** for specific use cases — especially useful in Livewire components or API filters.

---

## 🚀 Installation

```bash
composer require tes/laravel-relafilter
```

---

## ✅ Key Features

* Simple syntax using array-based filters
* Supports nested relationships (`employee.current_company.position.name`)
* Supports common query operators like `=`, `like`, `>`, `<`, `!=`
* Supports `whereBetween` for date ranges
* Perfect for use in Livewire or traditional controllers

---

## 🔧 Usage

### 🟢 In Livewire

```html
<input type="text"
       class="form-control"
       placeholder="Position"
       wire:model.live.debounce.300="search.current_company_position.position.name:like">
```

```php
use App\Models\Employee;

class EmployeeList extends Component
{
    public array $search = [];

    public function render()
    {
        $employees = Employee::filter($this->search)->get();

        return view('livewire.employee-list', compact('employees'));
    }
}
```

### 🟡 In Controller

```php
use App\Models\Employee;
use Illuminate\Http\Request;

public function index(Request $request)
{
    $employees = Employee::filter($request->input())->get();

    return view('employees.index', compact('employees'));
}
```

```html
<input type="text"
       class="form-control"
       placeholder="Position"
       name="search[current_company_position][position][name:like]">
```

---

## 🔍 Supported Operators

| Syntax    | SQL Equivalent | Example                      |
| --------- | -------------- | ---------------------------- |
| `:like`   | `LIKE`         | `name:like => John`          |
| `:=`      | `=`            | `status:= => active`         |
| `:!=`     | `!=`           | `type:!= => admin`           |
| `:>`      | `>`            | `age:> => 30`                |
| `:<`      | `<`            | `created_at:< => 2024-01-01` |
| *default* | `LIKE`         | If no operator provided      |

---

## 🧠 How It Works

The `filter()` method inspects the keys of the input array, and for any nested key (e.g. `company.manager.name:like`), it automatically applies the correct `whereHas` or `where` clause.

It also supports additional logic such as range filtering using `whereBetween`, particularly for date ranges.

### 📅 Date Range Example

If both `start` and `end` values are present in the filters, it will apply a `whereBetween` clause on the `start_date` column:

```php
if (isset($flatFilters['start']) && isset($flatFilters['end'])) {
    $query->whereBetween('start_date', [
        $flatFilters['start'],
        Carbon::parse($flatFilters['end'])->endOfDay(),
    ]);
}
```

### ✨ Example Filter Input (Array)

```php
[
    'company.name:like' => 'Tesla',
    'current_company_position.position.name:like' => 'Developer',
    'status' => 'active',
    'created_at:>' => '2024-01-01',
    'start' => '2024-06-01',
    'end' => '2024-06-30',
]
```

---

## 🧩 Integration

Make sure your model uses the `HasRelafilter` trait:

```php
use Tes\LaravelRelafilter\Traits\HasRelafilter;

class Employee extends Model
{
    use HasRelafilter;
}
```

---

## 📦 Package Status

This package is still in early development. Please report any issues or suggestions via GitHub.

---

## 📄 License

This package is open-source software licensed under the [MIT license](LICENSE).
