<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class AuditUserActivity
{
    private const SENSITIVE_INPUTS = [
        '_token',
        '_method',
        'password',
        'password_confirmation',
        'confirmPassword',
        'confarmPassword',
        'current_password',
        'token',
        'remember',
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldAudit($request, $response)) {
            return $response;
        }

        $actor = $request->user() ?? Auth::user();
        if (! $actor) {
            return $response;
        }

        $routeName = $request->route()?->getName() ?? '';
        $event = $this->eventFor($request, $routeName);
        $subjectType = $this->subjectTypeFor($routeName);
        $properties = Arr::except(
            $request->except([...self::SENSITIVE_INPUTS, ...array_keys($request->allFiles())]),
            self::SENSITIVE_INPUTS
        );

        if (isset($properties['items']) && is_array($properties['items'])) {
            $productIds = collect($properties['items'])
                ->pluck('product_id')
                ->filter()
                ->unique();

            $properties['product_names'] = Product::query()
                ->whereIn('id', $productIds)
                ->pluck('productName', 'id')
                ->all();
        }

        if (isset($properties['permission']) && is_array($properties['permission'])) {
            $properties['permission_names'] = Permission::query()
                ->whereIn('id', $properties['permission'])
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        UserActivityLog::create([
            'user_id' => $actor->getKey(),
            'user_name' => $actor->name,
            'user_email' => $actor->email,
            'event' => $event,
            'description' => $this->descriptionFor($event, $subjectType, $request),
            'subject_type' => $subjectType,
            'subject_id' => $this->subjectIdFor($request),
            'properties' => $properties ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }

    private function shouldAudit(Request $request, Response $response): bool
    {
        $routeName = $request->route()?->getName() ?? '';
        $path = trim($request->path(), '/');

        if (in_array($path, ['login', 'logout'], true) || $response->getStatusCode() >= 400) {
            return false;
        }

        $newFlashData = $request->hasSession() ? $request->session()->get('_flash.new', []) : [];
        if ($response->isRedirection() && in_array('errors', $newFlashData, true)) {
            return false;
        }

        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || str_ends_with($routeName, '.destroy')
            || str_ends_with($routeName, '.statusupdate');
    }

    private function eventFor(Request $request, string $routeName): string
    {
        if (str_ends_with($routeName, '.destroy')) {
            return 'delete';
        }

        if (str_ends_with($routeName, '.statusupdate')) {
            return 'update';
        }

        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'activity',
        };
    }

    private function subjectTypeFor(string $routeName): string
    {
        return match (true) {
            str_starts_with($routeName, 'supplier.ledger.payments.') => 'Supplier ledger payment',
            str_starts_with($routeName, 'customer.ledger.') => 'Customer ledger',
            str_starts_with($routeName, 'supplier.ledger.') => 'Supplier ledger',
            str_starts_with($routeName, 'employee-payment.') => 'Employee payment',
            str_starts_with($routeName, 'voucher-entries.') => 'Voucher entry',
            str_starts_with($routeName, 'sales.') => 'Sale',
            str_starts_with($routeName, 'product.') => 'Product',
            str_starts_with($routeName, 'category.') => 'Product category',
            str_starts_with($routeName, 'brand.') => 'Brand',
            str_starts_with($routeName, 'supplier.') => 'Supplier',
            str_starts_with($routeName, 'customer.') => 'Customer',
            str_starts_with($routeName, 'user.') => 'User',
            str_starts_with($routeName, 'roles.') => 'Role',
            str_starts_with($routeName, 'organization.') => 'Organization',
            default => 'System record',
        };
    }

    private function subjectIdFor(Request $request): ?string
    {
        $parameters = $request->route()?->parameters() ?? [];

        foreach (['id', 'status_id', 'supplier_id', 'employee'] as $key) {
            if (isset($parameters[$key]) && is_scalar($parameters[$key])) {
                return (string) $parameters[$key];
            }
        }

        return null;
    }

    private function descriptionFor(string $event, string $subjectType, Request $request): string
    {
        $verb = match ($event) {
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            default => 'Changed',
        };
        $label = $request->input('productName')
            ?? $request->input('name')
            ?? $request->input('invoice_id');

        return $verb . ' ' . $subjectType . ($label ? ': ' . $label : '');
    }
}
