<template>

    <Row :gutter="4">

        <Col :span="24" class="mb-2">

            <!-- Billing Event Instructions -->
            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Structure the <span class="font-italic text-success font-weight-bold">Payment Success Message</span>
                that should be displayed on the <span class="font-italic text-success font-weight-bold">USSD Screen</span>
                and the outgoing <span class="font-italic text-success font-weight-bold">SMS</span>
            </Alert>

        </Col>

        <Col :span="24" class="mb-2">

            <textOrCodeEditor
                class="mb-2"
                size="small"
                title="USSD Message"
                :value="event.event_data.payment_success.display_message"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"
                :placeholder="'Payment successful! Thank you for your purchase. Reference #{{ billing.reference_number }}'">
            </textOrCodeEditor>

        </Col>

        <Col :span="24" class="mb-2">

            <textOrCodeEditor
                class="mb-2"
                size="small"
                title="SMS Message"
                :value="event.event_data.payment_success.sms_message"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"
                :placeholder="smsMessagePlaceholder">
            </textOrCodeEditor>

        </Col>

        <Col :span="24" class="mb-2">

            <table class="w-100 table table-hover border">
                <thead class="bg-grey-light border">
                    <tr>
                        <th colspan="1"><span class="d-block my-2 ml-2">Billing Tags</span></th>
                        <th colspan="1"><span class="d-block my-2">Description</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(billing_tag, index) in billing_tags" :key="index">
                        <td colspan="1">
                            <span class="d-block mt-2 mb-2">
                                <span class="dynamic-content-label">{{ billing_tag.tag }}</span>
                            </span>
                        </td>
                        <td colspan="1">
                            <span class="d-block mt-2 mb-2">{{ billing_tag.desc }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>

        </Col>

    </Row>

</template>

<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';

    export default {
        components: { textOrCodeEditor },
        props: {
            index: {
                type: Number,
                default: null
            },
            event: {
                type: Object,
                default: null
            },
            events: {
                type: Array,
                default: () => []
            },
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
        },
        data(){
            return{
                billing_tags: [
                    {
                        tag: '{{ billing.amount }}',
                        desc: 'The total amount paid'
                    },
                    {
                        tag: '{{ billing.description }}',
                        desc: 'The description of the bill'
                    },
                    {
                        tag: '{{ billing.items }}',
                        desc: 'The list of items as an [Array]'
                    },
                    {
                        tag: '{{ billing.items_list }}',
                        desc: 'The list of items as a [String]'
                    }
                ]
            }
        },
        computed: {
            smsMessagePlaceholder(){
                return 'Payment successful! You paid {{ billing.grand_total }} for "{{ billing.description }}"';
            }
        }
    };
  
</script>