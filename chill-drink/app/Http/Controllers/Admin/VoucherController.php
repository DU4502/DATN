<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $query = Voucher::query();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        match ($request->query('status')) {
            'active' => $query->where('status', true)
                ->where(fn ($builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($builder) => $builder->whereNull('expires_at')->orWhere('expires_at', '>=', now())),
            'inactive' => $query->where('status', false),
            'scheduled' => $query->where('status', true)->where('starts_at', '>', now()),
            'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<', now()),
            default => null,
        };

        $vouchers = $query
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Voucher::count(),
            'active' => Voucher::where('status', true)
                ->where(fn ($builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($builder) => $builder->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
                ->count(),
            'scheduled' => Voucher::where('status', true)->where('starts_at', '>', now())->count(),
            'used' => (int) Voucher::sum('used_count'),
        ];

        return view('admin.vouchers.index', [
            'vouchers' => $vouchers,
            'stats' => $stats,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.vouchers.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data = $this->preparedData($request, $data);
        $data['created_at'] = now();

        Voucher::create($data);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Đã tạo voucher mới.');
    }

    public function edit(Voucher $voucher): View
    {
        return view('admin.vouchers.edit', $this->formOptions($voucher));
    }

    public function update(Request $request, Voucher $voucher): RedirectResponse
    {
        $data = $this->validatedData($request, $voucher);
        $voucher->update($this->preparedData($request, $data));

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Đã cập nhật voucher.');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Đã xóa voucher.');
    }

    private function validatedData(Request $request, ?Voucher $voucher = null): array
    {
        $voucherId = $voucher?->id;
        $rankKeys = array_keys(Voucher::RANK_LABELS);

        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($voucherId),
            ],
            'type' => ['required', Rule::in([Voucher::TYPE_FIXED, Voucher::TYPE_PERCENT])],
            'value' => ['required', 'integer', 'min:1'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'min_order' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'required_rank' => ['nullable', Rule::in($rankKeys)],
            'point_cost' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.required' => 'Vui lòng nhập mã voucher.',
            'code.regex' => 'Mã voucher chỉ được dùng chữ, số, dấu gạch ngang hoặc gạch dưới.',
            'code.unique' => 'Mã voucher này đã tồn tại.',
            'type.required' => 'Vui lòng chọn loại giảm giá.',
            'value.required' => 'Vui lòng nhập giá trị giảm.',
            'value.integer' => 'Giá trị giảm phải là số nguyên.',
        ]);

        $validator->after(function ($validator) use ($request, $voucher) {
            if ($request->input('type') === Voucher::TYPE_PERCENT && (int) $request->input('value') > 100) {
                $validator->errors()->add('value', 'Voucher phần trăm không được lớn hơn 100%.');
            }

            foreach ([
                'starts_at' => 'Ngày bắt đầu',
                'expires_at' => 'Ngày hết hạn',
            ] as $field => $label) {
                if ($request->filled($field) && $this->dateInputChanged($voucher, $field, (string) $request->input($field))) {
                    try {
                        $submittedDate = Carbon::parse((string) $request->input($field))->startOfMinute();

                        if ($submittedDate->lt(now()->startOfMinute())) {
                            $validator->errors()->add($field, "{$label} không được là thời gian trong quá khứ.");
                        }
                    } catch (\Throwable) {
                        // The base `date` rule handles invalid date formats.
                    }
                }
            }

            $dateWindowChanged = ! $voucher
                || ($request->filled('starts_at') && $this->dateInputChanged($voucher, 'starts_at', (string) $request->input('starts_at')))
                || ($request->filled('expires_at') && $this->dateInputChanged($voucher, 'expires_at', (string) $request->input('expires_at')));

            if ($request->filled('expires_at') && $dateWindowChanged) {
                try {
                    $startsAt = $request->filled('starts_at')
                        ? Carbon::parse((string) $request->input('starts_at'))->startOfMinute()
                        : now()->startOfMinute();
                    $expiresAt = Carbon::parse((string) $request->input('expires_at'))->startOfMinute();

                    if ($expiresAt->lte($startsAt)) {
                        $validator->errors()->add('expires_at', 'Ngày hết hạn phải sau ngày bắt đầu.');
                    }
                } catch (\Throwable) {
                    // The base `date` rule handles invalid date formats.
                }
            }
        });

        return $validator->validate();
    }

    private function preparedData(Request $request, array $data): array
    {
        return [
            'code' => strtoupper(trim((string) $data['code'])),
            'type' => $data['type'],
            'value' => (int) $data['value'],
            'max_discount' => $data['type'] === Voucher::TYPE_PERCENT ? ($data['max_discount'] ?? null) : null,
            'description' => $data['description'] ?? null,
            'min_order' => (int) ($data['min_order'] ?? 0),
            'usage_limit' => (int) ($data['usage_limit'] ?? 0),
            'starts_at' => $data['starts_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'status' => $request->boolean('status'),
            'required_rank' => $data['required_rank'] ?? null,
            'point_cost' => (int) ($data['point_cost'] ?? 0),
            'is_redeemable' => $request->boolean('is_redeemable'),
        ];
    }

    private function dateInputChanged(?Voucher $voucher, string $field, string $value): bool
    {
        if (! $voucher) {
            return true;
        }

        $current = $voucher->{$field};

        if (! $current) {
            return $value !== '';
        }

        try {
            return ! Carbon::parse($value)
                ->startOfMinute()
                ->equalTo($current->copy()->startOfMinute());
        } catch (\Throwable) {
            return true;
        }
    }

    private function formOptions(?Voucher $voucher = null): array
    {
        return [
            'voucher' => $voucher,
            'typeOptions' => [
                Voucher::TYPE_FIXED => 'Giảm cố định (VNĐ)',
                Voucher::TYPE_PERCENT => 'Giảm theo phần trăm',
            ],
            'rankOptions' => Voucher::RANK_LABELS,
        ];
    }

    private function statusOptions(): array
    {
        return [
            'active' => 'Đang hoạt động',
            'scheduled' => 'Đã lên lịch',
            'expired' => 'Đã hết hạn',
            'inactive' => 'Đã tắt',
        ];
    }
}
