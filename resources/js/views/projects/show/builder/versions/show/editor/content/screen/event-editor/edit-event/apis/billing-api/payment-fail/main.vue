<template>

    <Row :gutter="4">

        <Col :span="24" class="mb-2">

            <!-- Billing Event Instructions -->
            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Structure the <span class="font-italic text-success font-weight-bold">Payment Failed Message</span>
                that should be displayed on the <span class="font-italic text-success font-weight-bold">USSD Screen</span>
            </Alert>

        </Col>

        <Col :span="24" class="mb-2">

            <textOrCodeEditor
                class="mb-2"
                size="small"
                title="USSD Message"
                :placeholder="'Sorry, payment failed!'"
                :value="event.event_data.payment_fail.display_message"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
            </textOrCodeEditor>

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

            }
        }
    };
  
</script>