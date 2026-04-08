<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'

const { t } = useI18n()
const props = defineProps({ invoice: Object })

async function printInvoice() {
    try {
        await fetch(route('invoices.print-alert', props.invoice.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
    } catch (_) { }
    window.print()
}

function fmt(n) {
    return Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(d) {
    if (!d) return ''
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    const raw = String(d).split('T')[0].split('-')
    const day = raw[2].padStart(2, '0')
    const month = months[parseInt(raw[1], 10) - 1]
    const year = raw[0]
    return `${day}-${month}-${year}`
}

const currencySymbol = (c) => c === 'KHR' ? '៛' : '$'

const paymentStages = [
    { id: 'booking', label: t('stage_first_deposit') },
    { id: 'service_payment', label: t('stage_blind') },
    { id: 'down_payment', label: t('stage_down') },
    { id: 'installment', label: t('stage_final') },
    { id: 'full_payment', label: t('stage_full') },
]
</script>

<template>

    <Head :title="t('invoice_number') + ': ' + invoice.invoice_number" />
    <AppLayout>
        <template #title>{{ t('invoices') }} — {{ t('view') }}</template>

        <div class="max-w-3xl mx-auto">
            <!-- Actions (hidden on print) -->
            <div class="no-print flex gap-3 mb-4">
                <Link :href="route('invoices.index')"
                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">← {{ t('back') }}
                </Link>
                <Link :href="route('invoices.edit', invoice.id)"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">{{ t('edit') }}</Link>
                <a :href="route('invoices.download', invoice.id)"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">{{ t('download')
                    }}</a>
                <button @click="printInvoice"
                    class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">{{
                        t('print') }}</button>
            </div>

            <!-- Printable Document -->
            <div id="printable" class="bg-white border border-gray-300 p-8 text-xs font-sans leading-snug">

                <!-- Page Header -->
                <div class="flex items-start mb-1">
                    <div class="shrink-0 w-20">
                        <img src="/images/LogoBYD.png" alt="BYD" class="h-12" style="margin-top: -5px;" />
                    </div>
                    <div class="flex-1 text-center">
                        <p class="font-bold text-sm leading-tight">{{ t('company_name') }}</p>
                        <p class="text-gray-600 leading-tight">{{ t('company_address') }}</p>
                        <p class="text-gray-600 leading-tight">{{ t('company_phone_line') }}</p>
                    </div>
                </div>

                <hr class="border-gray-500 mb-2" />

                <!-- Document Title -->
                <div class="text-center my-2">
                    <div class="text-right">
                        NO: {{ invoice.invoice_number }}
                    </div>
                    <p class="text-sm font-semibold">ឯកសារទទួលប្រាក់</p>
                    <p class="text-base font-bold uppercase tracking-widest">
                        {{ invoice.invoice_type === 'car_sale' ? t('official_receipt') : 'SERVICE INVOICE' }}
                    </p>
                </div>

                <!-- Info Rows -->
                <table class="w-full border-collapse border border-gray-400 mb-0">
                    <tr>
                        <td class="py-1 px-2 w-1/2 pr-4">
                            <span class="text-gray-600">បានទទួលពី /Received From:</span>
                            <span class="font-semibold ml-1">{{ invoice.customer_name }}</span>
                        </td>
                        <td class="py-1 px-2 text-right">
                            <span class="text-gray-600">កាលបរិច្ឆេទ (Date) :</span>
                            <span class="font-semibold ml-1">{{ fmtDate(invoice.date) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-1 px-2 pr-4">
                            <span class="text-gray-600">លេខទូរស័ព្ទអតិថិជន/ Customer number :</span>
                            <span class="ml-1">{{ invoice.customer_phone }}</span>
                        </td>
                        <td class="py-1 px-2 text-right">
                            <span class="text-gray-600">សាច់ប្រាក់ / Cash :</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-0.5 px-2 pr-4">
                            <span class="text-gray-600">អ្នកលក់/Sales:</span>
                            <span class="ml-1">{{ invoice.creator?.name }}</span>
                        </td>
                        <td class="py-0.5 px-2 text-right">
                            <span class="text-gray-600">ការផ្ទេរប្រាក់តាមធនាគារ /Bank Transfer: ✓</span>
                            <span class="font-semibold ml-1">{{ invoice.bank_reference }}</span>
                        </td>
                    </tr>
                    <tr style="height:22px;">
                        <td class="px-2"></td>
                        <td class="px-2"></td>
                    </tr>
                </table>

                <!-- Payment Stage Checkboxes -->
                <div class="flex items-center gap-5 border border-gray-400 border-t-0 px-3 py-1.5 mb-3">
                    <template v-for="stage in paymentStages" :key="stage.id">
                        <label class="flex items-center gap-1 cursor-default">
                            <span
                                class="inline-flex items-center justify-center w-4 h-4 border border-gray-600 text-xs leading-none">
                                {{ invoice.payment_category === stage.id ? '✓' : '' }}
                            </span>
                            {{ stage.label }}
                        </label>
                    </template>
                </div>

                <!-- Items Table -->
                <table class="w-full border-collapse border border-gray-400 mb-0">
                    <thead>
                        <tr>
                            <th class="border border-gray-400 py-1.5 px-1.5 text-center w-8">No<br><span
                                    class="text-gray-500">ល.រ</span></th>
                            <th class="border border-gray-400 py-1.5 px-2 text-center">Description<br><span
                                    class="text-gray-500">បរិយាយមុខទំនិញ</span></th>
                            <th class="border border-gray-400 py-1.5 px-1.5 text-center w-14">Quantity<br><span
                                    class="text-gray-500">បរិមាណ</span></th>
                            <th class="border border-gray-400 py-1.5 px-1.5 text-center w-28">Unit Price<br><span
                                    class="text-gray-500">ថ្លៃឯកតា</span></th>
                            <th class="border border-gray-400 py-1.5 px-1.5 text-center w-24">Amount<br><span
                                    class="text-gray-500">ថ្លៃទំនិញ</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Item row -->
                        <tr>
                            <td class="border-l border-r border-gray-400 px-1.5 text-center align-top pt-1.5">1</td>
                            <td class="border-l border-r border-gray-400 px-2 align-top pt-1.5"
                                style="min-height:130px;">
                                <div>Model: {{ invoice.car_model }}</div>
                                <div class="mt-0.5">VIN: {{ invoice.notes ?? '' }}</div>
                            </td>
                            <td class="border-l border-r border-gray-400 px-1.5 text-center align-top pt-1.5">{{
                                invoice.quantity }}</td>
                            <td
                                class="border-l border-r border-gray-400 px-1.5 text-right align-top pt-1.5 whitespace-nowrap">
                                <span v-if="Number(invoice.unit_price) > 0">{{ currencySymbol(invoice.currency) }} {{
                                    fmt(invoice.unit_price) }}</span>
                            </td>
                            <td class="border-l border-r border-gray-400 px-1.5 text-right align-top pt-1.5">
                                {{ currencySymbol(invoice.currency) }} {{ fmt(invoice.sub_total) }}
                            </td>
                        </tr>
                        <!-- Spacer rows -->
                        <tr style="height:32px;">
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                        </tr>
                        <tr style="height:32px;">
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                        </tr>
                        <tr style="height:32px;">
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                        </tr>
                        <tr style="height:32px;">
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                            <td class="border-l border-r border-gray-400"></td>
                        </tr>
                    </tbody>
                    <!-- VAT row (only when VAT is applied) -->
                    <tfoot>
                        <tr v-if="invoice.with_vat">
                            <td colspan="4" class="border border-gray-400 py-1 px-2 text-right">{{ t('vat') }} ({{
                                invoice.vat_rate }}%)</td>
                            <td class="border border-gray-400 py-1 px-2 text-right">{{ currencySymbol(invoice.currency)
                                }} {{
                                    fmt(invoice.vat_amount) }}</td>
                        </tr>
                        <tr class="border-t border-gray-400">
                            <td colspan="4" class="border border-gray-400 py-1.5 px-2 text-right font-semibold">Balance
                                to Pay
                            </td>
                            <td class="border border-gray-400 py-1.5 px-2 text-right font-bold">
                                {{ currencySymbol(invoice.currency) }} {{ fmt(invoice.grand_total) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="border border-gray-400 py-1.5 px-2 text-right font-semibold">
                                ទឹកប្រាក់ដែលត្រូវបង់ (Balance to Pay)</td>
                            <td class="border border-gray-400 py-1.5 px-2 text-right font-bold">
                                {{ currencySymbol(invoice.currency) }} {{ fmt(invoice.grand_total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Bank Info -->
                <div class="mt-3 space-y-0.5">
                    <div><span class="text-gray-600 mr-2">Bank Name</span>{{ t('bank_name_value') }}</div>
                    <div><span class="text-gray-600 mr-2">Beneficiary Name</span>{{ t('bank_beneficiary') }}</div>
                    <div><span class="text-gray-600 mr-2">Account Number</span>{{ t('bank_account_no') }}</div>
                </div>

                <!-- Signatures -->
                <div class="flex justify-between mt-10">
                    <div class="text-center w-52">
                        <p class="font-semibold mb-1">ទទួលដោយ:</p>
                        <p class="mb-10">Received By:</p>
                        <div class="border-t border-gray-400 pt-1 text-left">
                            <p>ឈ្មោះ: {{ invoice.creator?.name }}</p>
                            <p>កាលបរិច្ឆេទ: {{ fmtDate(invoice.date) }}</p>
                        </div>
                    </div>
                    <div class="text-center w-52">
                        <p class="font-semibold mb-1">ហត្ថលេខាអតិថិជន:</p>
                        <p class="mb-10">{{ t('customers_signature') }}</p>
                        <div class="border-t border-gray-400 pt-1 text-left">
                            <p>ឈ្មោះ:</p>
                            <p>កាលបរិច្ឆេទ:</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    .no-print {
        display: none !important;
    }

    nav,
    header,
    aside,
    footer {
        display: none !important;
    }

    body {
        background: white !important;
    }

    #printable {
        border: none !important;
        box-shadow: none !important;
        padding: 12mm !important;
    }

    .bg-red-600 {
        background-color: #dc2626 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
