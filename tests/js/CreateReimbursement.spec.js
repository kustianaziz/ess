import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import Create from '../../resources/js/Pages/Pengajuan/Reimbursement/Create.vue';

// Mock Inertia and Lucide-vue-next
vi.mock('@inertiajs/vue3', () => {
  const { reactive } = require('vue');
  return {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' },
    useForm: (initial) => {
      const form = reactive({
        ...initial,
        processing: false,
        errors: {},
        post: vi.fn(),
      });
      globalThis.__MOCK_FORM__ = form;
      return form;
    }
  };
});

vi.mock('lucide-vue-next', () => ({
  ArrowLeft: { template: '<span></span>' },
  ArrowRight: { template: '<span></span>' },
  Save: { template: '<span></span>' },
  Send: { template: '<span></span>' },
  FileText: { template: '<span></span>' },
}));

describe('Create Reimbursement Form', () => {
  const AuthenticatedLayout = { template: '<div><slot /></div>' };
  const Stepper = { template: '<div></div>' };
  const FileUploader = { template: '<div></div>', props: ['modelValue'] };
  const InputError = { template: '<div class="input-error" v-if="message">{{ message }}</div>', props: ['message'] };

  const defaultProps = {
    applicant: {
      name: 'John Doe',
      nik: '12345',
      division: 'IT',
      position: 'Developer',
      submission_date: '2026-09-06'
    },
    expenseTypes: [
      { id: 1, name: 'Transport' },
      { id: 2, name: 'Meals' }
    ]
  };

  it('renders applicant information correctly', () => {
    const wrapper = mount(Create, {
      global: {
        stubs: { AuthenticatedLayout, Stepper, FileUploader, InputError },
        mocks: { route: () => '' }
      },
      props: defaultProps
    });
    
    const inputs = wrapper.findAll('input');
    const inputValues = inputs.map(i => i.element.value);
    expect(inputValues).toContain('John Doe');
    expect(inputValues).toContain('12345');
    
    expect(wrapper.text()).toContain('Transport');
  });

  it('formats amount input correctly', async () => {
    const wrapper = mount(Create, {
      global: {
        stubs: { AuthenticatedLayout, Stepper, FileUploader, InputError },
        mocks: { route: () => '' }
      },
      props: defaultProps
    });

    const amountInput = wrapper.find('input[placeholder="Rp 0"]');
    await amountInput.setValue('150000');
    
    // Check if the formatted value is updated
    expect(amountInput.element.value).toBe('Rp 150.000');
  });
  
  it('disables submit buttons when processing', async () => {
    const wrapper = mount(Create, {
      global: {
        stubs: { AuthenticatedLayout, Stepper, FileUploader, InputError },
        mocks: { route: () => '' }
      },
      props: defaultProps
    });
    
    globalThis.__MOCK_FORM__.processing = true;
    globalThis.__MOCK_FORM__.action = 'draft';
    await wrapper.vm.$nextTick();
    
    const buttons = wrapper.findAll('button');
    const draftBtn = buttons.find(b => b.text().includes('Menyimpan...'));
    expect(draftBtn).toBeDefined();
    expect(draftBtn.element.disabled).toBe(true);
  });

  it('shows error state when API validation fails', async () => {
    const wrapper = mount(Create, {
      global: {
        stubs: { AuthenticatedLayout, Stepper, FileUploader, InputError },
        mocks: { route: () => '' }
      },
      props: defaultProps
    });
    
    globalThis.__MOCK_FORM__.errors = { amount: 'Nominal tidak valid dari server' };
    await wrapper.vm.$nextTick();
    
    const errorEl = wrapper.find('.input-error');
    expect(errorEl.exists()).toBe(true);
    expect(errorEl.text()).toContain('Nominal tidak valid dari server');
  });

  it('shows error when navigating to next step with incomplete data', async () => {
    const wrapper = mount(Create, {
      global: {
        stubs: { AuthenticatedLayout, Stepper, FileUploader, InputError },
        mocks: { route: () => '' }
      },
      props: defaultProps
    });
    
    const nextBtn = wrapper.findAll('button').find(b => b.text().includes('Selanjutnya'));
    await nextBtn.trigger('click');
    
    expect(wrapper.text()).toContain('Harap lengkapi semua kolom informasi reimbursement.');
  });

  it('shows empty state for expenseTypes', () => {
    const wrapper = mount(Create, {
      global: {
        stubs: { AuthenticatedLayout, Stepper, FileUploader, InputError },
        mocks: { route: () => '' }
      },
      props: {
        ...defaultProps,
        expenseTypes: []
      }
    });

    const select = wrapper.find('select');
    expect(select.text()).toContain('Tidak ada jenis pengeluaran');
  });
});
