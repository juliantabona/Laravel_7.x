<template>

    <Row :gutter="4">

        <Col :span="24" class="mb-2">

            <!-- Billing Event Instructions -->
            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Complete the <span class="font-italic text-success font-weight-bold">Billing Details</span>
                by setting the <span class="font-italic text-success font-weight-bold">Bill Description</span>,
                <span class="font-italic text-success font-weight-bold">Bill Items</span> and 
                <span class="font-italic text-success font-weight-bold">Bill Price</span> to
                allow for automated payment processing.
            </Alert>

        </Col>

        <Col :span="24" class="mb-2">

            <textOrCodeEditor
                class="mb-2"
                size="small"
                title="Description"
                :placeholder="'{{ cart.description }}'"
                :value="event.event_data.description"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
            </textOrCodeEditor>

        </Col>

        <Col :span="24" class="mb-2">

            <textOrCodeEditor
                class="mb-2"
                size="small"
                title="Grand Total Price"
                :placeholder="'{{ cart.price }}'"
                :value="event.event_data.price"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
            </textOrCodeEditor>

        </Col>

        <Col :span="24">

            <Row :gutter="12">

                <!-- Foreach Items Group Reference -->
                <Col :span="groupReferenceUsesCodeEditorMode ? 24 : 12">
                
                    <!-- Group Reference -->
                    <textOrCodeEditor
                        size="small"
                        title="Foreach"
                        :placeholder="'{{ cart.items }}'"
                        :value="event.event_data.line_items.group_reference"
                        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                    </textOrCodeEditor>

                </Col>
                
                <!-- As Label -->
                <Col :span="groupReferenceUsesCodeEditorMode ? 24 : 12" class="d-flex pt-4">

                    <span class="d-block text-center mt-1 mr-3">As</span>
                
                    <!-- Template Reference Name -->
                    <referenceNameInput 
                        v-model="event.event_data.line_items.template_reference_name" class="w-100"
                        :version="version" :screen="screen" :display="display"
                        :isRequired="false" :placeholder="'item'">
                    </referenceNameInput>
                
                </Col>
                
                <Col :span="24">

                    <div class="bg-grey-light border my-3 py-3 px-2">

                        <!-- Item Name -->
                        <textOrCodeEditor
                            class="mb-2"
                            size="medium"
                            title="Item Name"
                            :placeholder="'{{ item.name }}'"
                            sampleCodeTemplate="ussd_service_dynamic_select_option_display_name_sample_code"
                            :value="event.event_data.line_items.template_name">
                        </textOrCodeEditor>

                        <!-- Item Description -->
                        <textOrCodeEditor
                            size="medium"
                            title="Item Description"
                            :placeholder="'{{ item.description }}'"
                            sampleCodeTemplate="ussd_service_dynamic_select_option_value_sample_code"
                            :value="event.event_data.line_items.template_description">
                        </textOrCodeEditor>

                        <!-- Item Quantity -->
                        <textOrCodeEditor
                            size="medium"
                            title="Item Quantity"
                            :placeholder="'{{ item.quantity }}'"
                            sampleCodeTemplate="ussd_service_dynamic_select_option_value_sample_code"
                            :value="event.event_data.line_items.template_quantity">
                        </textOrCodeEditor>

                        <!-- Item Price -->
                        <textOrCodeEditor
                            size="medium"
                            title="Item Price"
                            :placeholder="'{{ item.price }}'"
                            sampleCodeTemplate="ussd_service_dynamic_select_option_value_sample_code"
                            :value="event.event_data.line_items.template_price">
                        </textOrCodeEditor>

                    </div>
                
                </Col>

            </Row>

        </Col>

    </Row>

</template>

<script>

    //  Get the loader
    import Loader from './../../../../../../../../../../../../../../components/_common/loaders/default.vue';

    import referenceNameInput from './../../../../../referenceNameInput.vue';

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';

    export default {
        components: { Loader, referenceNameInput, textOrCodeEditor },
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
        },
        computed: {
            groupReferenceUsesCodeEditorMode(){
                return this.event.event_data.line_items.group_reference.code_editor_mode;
            }
        },
        methods: {
            
        }
    };
  
</script>