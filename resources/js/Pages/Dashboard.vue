<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import axios from 'axios';

const page = usePage();
const balance = ref(0);
const transactions = ref([]);
const receiverId = ref('');
const amount = ref('');
const loading = ref(false);
const error = ref('');
const showModal = ref(false);
const validatingReceiver = ref(false);
const receiverValidation = ref({ valid: null, message: '', user: null });
let validationTimeout = null;

const showAddMoneyModal = ref(false);
const addMoneyAmount = ref('');
const addingMoney = ref(false);

const formattedBalance = computed(() => {
    return typeof balance.value === 'number' ? balance.value.toFixed(2) : '0.00';
});

const totalDeduction = computed(() => {
    const amt = parseFloat(amount.value) || 0;
    const commission = amt * 0.015; // 1.5%
    const total = amt + commission;
    return {
        amount: amt.toFixed(2),
        commission: commission.toFixed(2),
        total: total.toFixed(2)
    };
});

const fetchTransactions = async () => {
    try {
        const response = await axios.get('/api/transactions');
        transactions.value = response.data.transactions.data;
        balance.value = parseFloat(response.data.balance) || 0;
    } catch (err) {
        console.error('Failed to fetch transactions', err);
    }
};

const validateReceiver = async () => {
    if (!receiverId.value) {
        receiverValidation.value = { valid: null, message: '', user: null };
        return;
    }

    validatingReceiver.value = true;

    try {
        const response = await axios.get(`/api/validate-receiver/${receiverId.value}`);
        receiverValidation.value = {
            valid: true,
            message: `Sending to: ${response.data.user.name}`,
            user: response.data.user
        };
    } catch (err) {
        receiverValidation.value = {
            valid: false,
            message: err.response?.data?.message || 'Invalid receiver',
            user: null
        };
    } finally {
        validatingReceiver.value = false;
    }
};

// Watch receiver ID and validate with debounce
watch(receiverId, (newValue) => {
    if (validationTimeout) {
        clearTimeout(validationTimeout);
    }

    if (!newValue) {
        receiverValidation.value = { valid: null, message: '', user: null };
        return;
    }

    validationTimeout = setTimeout(() => {
        validateReceiver();
    }, 500);
});

const openModal = () => {
    showModal.value = true;
    error.value = '';
    receiverId.value = '';
    amount.value = '';
    receiverValidation.value = { valid: null, message: '', user: null };
};

const closeModal = () => {
    showModal.value = false;
    error.value = '';
    receiverId.value = '';
    amount.value = '';
    receiverValidation.value = { valid: null, message: '', user: null };
    if (validationTimeout) {
        clearTimeout(validationTimeout);
    }
};

const sendMoney = async () => {
    if (!receiverId.value || !amount.value) {
        error.value = 'Please fill in all fields';
        return;
    }

    if (receiverValidation.value.valid === false) {
        error.value = 'Please enter a valid receiver ID';
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        await axios.post('/api/transactions', {
            receiver_id: receiverId.value,
            amount: parseFloat(amount.value)
        });

        closeModal();
        await fetchTransactions();
    } catch (err) {
        error.value = err.response?.data?.message || 'Transaction failed';
    } finally {
        loading.value = false;
    }
};

const openAddMoneyModal = () => {
    showAddMoneyModal.value = true;
    addMoneyAmount.value = '';
    error.value = '';
};

const closeAddMoneyModal = () => {
    showAddMoneyModal.value = false;
    addMoneyAmount.value = '';
    error.value = '';
};

const addMoney = async () => {
    if (!addMoneyAmount.value || parseFloat(addMoneyAmount.value) <= 0) {
        error.value = 'Please enter a valid amount';
        return;
    }

    addingMoney.value = true;
    error.value = '';

    try {
        const response = await axios.post('/api/add-money', {
            amount: parseFloat(addMoneyAmount.value)
        });

        balance.value = parseFloat(response.data.balance) || 0;
        closeAddMoneyModal();
        await fetchTransactions();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to add money';
    } finally {
        addingMoney.value = false;
    }
};

onMounted(() => {
    fetchTransactions();

    // Listen for real-time balance updates on private channel
    if (window.Echo) {
        const channelName = `user.${page.props.auth.user.id}`;
        console.log('Subscribing to channel:', channelName);

        window.Echo.private(channelName)
            .listen('.BalanceUpdated', (e) => {
                console.log('BalanceUpdated event received:', e);
                balance.value = parseFloat(e.balance) || 0;
                fetchTransactions();
            })
            .error((error) => {
                console.error('Echo subscription error:', error);
            });
    } else {
        console.error('Echo is not initialized');
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`user.${page.props.auth.user.id}`);
    }
});
</script>

<template>
    <AppLayout title="Transactions">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Transactions
                </h2>
                <div class="flex gap-3">
                    <SecondaryButton @click="openAddMoneyModal">
                        Add Money
                    </SecondaryButton>
                    <PrimaryButton @click="openModal">
                        Send Money
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Balance Display -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-2">Current Balance</h3>
                    <p class="text-3xl font-bold text-green-600">${{ formattedBalance }}</p>
                </div>

                <!-- Transaction History -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Transaction History</h3>

                    <div v-if="transactions.length === 0" class="text-gray-500">
                        No transactions yet
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="transaction in transactions" :key="transaction.id"
                            class="border-b pb-3 last:border-0">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="font-semibold">
                                        <span v-if="transaction.sender_id === page.props.auth.user.id"
                                            class="text-red-600">
                                            Sent to {{ transaction.receiver?.name }}
                                        </span>
                                        <span v-else class="text-green-600">
                                            Received from {{ transaction.sender?.name }}
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ new Date(transaction.created_at).toLocaleString() }}
                                    </p>
                                    <p v-if="transaction.sender_id === page.props.auth.user.id && transaction.balance_after !== null"
                                        class="text-xs text-gray-600 mt-1">
                                        Balance after: ${{ parseFloat(transaction.balance_after).toFixed(2) }}
                                    </p>
                                </div>
                                <div class="text-right ml-4">
                                    <p class="font-bold"
                                        :class="transaction.sender_id === page.props.auth.user.id ? 'text-red-600' : 'text-green-600'">
                                        {{ transaction.sender_id === page.props.auth.user.id ? '-' : '+' }}${{
                                            parseFloat(transaction.amount).toFixed(2) }}
                                    </p>
                                    <p v-if="transaction.sender_id === page.props.auth.user.id"
                                        class="text-xs text-gray-500">
                                        Fee: ${{ parseFloat(transaction.commission_fee).toFixed(2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Send Money Modal -->
        <DialogModal :show="showModal" @close="closeModal">
            <template #title>
                Send Money
            </template>

            <template #content>
                <div class="space-y-4">
                    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ error }}
                    </div>

                    <div>
                        <InputLabel for="receiver_id" value="Receiver ID" />
                        <TextInput id="receiver_id" v-model="receiverId" type="number" class="mt-1 block w-full"
                            :disabled="loading" />

                        <!-- Validation Messages -->
                        <div v-if="validatingReceiver" class="mt-2 text-sm text-gray-500">
                            <span class="inline-flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Validating...
                            </span>
                        </div>

                        <div v-else-if="receiverValidation.valid === true"
                            class="mt-2 text-sm text-green-600 flex items-center">
                            <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ receiverValidation.message }}
                        </div>

                        <div v-else-if="receiverValidation.valid === false"
                            class="mt-2 text-sm text-red-600 flex items-center">
                            <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ receiverValidation.message }}
                        </div>
                    </div>

                    <div>
                        <InputLabel for="amount" value="Amount" />
                        <TextInput id="amount" v-model="amount" type="number" step="0.01" class="mt-1 block w-full"
                            :disabled="loading" />
                        <p class="mt-1 text-sm text-gray-500">Commission: 1.5% will be charged</p>
                    </div>

                    <div v-if="amount && parseFloat(amount) > 0"
                        class="bg-gray-50 p-4 rounded-md border border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Transaction Summary</h4>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Amount to send:</span>
                                <span class="font-medium">${{ totalDeduction.amount }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Commission fee (1.5%):</span>
                                <span class="font-medium">${{ totalDeduction.commission }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-300">
                                <span class="text-gray-900 font-semibold">Total to be deducted:</span>
                                <span class="font-bold text-red-600">${{ totalDeduction.total }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal" :disabled="loading">
                    Cancel
                </SecondaryButton>

                <PrimaryButton class="ms-3" @click="sendMoney"
                    :class="{ 'opacity-25': loading || receiverValidation.valid !== true }"
                    :disabled="loading || receiverValidation.valid !== true">
                    {{ loading ? 'Sending...' : 'Send Money' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Add Money Modal -->
        <DialogModal :show="showAddMoneyModal" @close="closeAddMoneyModal">
            <template #title>
                Add Money
            </template>

            <template #content>
                <div class="space-y-4">
                    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ error }}
                    </div>

                    <div>
                        <InputLabel for="add_amount" value="Amount to Add" />
                        <TextInput id="add_amount" v-model="addMoneyAmount" type="number" step="0.01"
                            class="mt-1 block w-full" :disabled="addingMoney" placeholder="Enter amount" />
                        <p class="mt-1 text-sm text-gray-500">Enter the amount you want to add to your account</p>
                    </div>

                    <div v-if="addMoneyAmount && parseFloat(addMoneyAmount) > 0"
                        class="bg-green-50 p-4 rounded-md border border-green-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700">Amount to be added:</span>
                            <span class="text-lg font-bold text-green-600">${{ parseFloat(addMoneyAmount).toFixed(2)
                                }}</span>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeAddMoneyModal" :disabled="addingMoney">
                    Cancel
                </SecondaryButton>

                <PrimaryButton class="ms-3" @click="addMoney"
                    :class="{ 'opacity-25': addingMoney || !addMoneyAmount || parseFloat(addMoneyAmount) <= 0 }"
                    :disabled="addingMoney || !addMoneyAmount || parseFloat(addMoneyAmount) <= 0">
                    {{ addingMoney ? 'Adding...' : 'Add Money' }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
