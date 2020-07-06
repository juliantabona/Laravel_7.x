<template>

    <div>

        <!-- Redirect Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Redirect</span> to go to another 
            <span class="font-italic text-success font-weight-bold">Service Code</span> e.g
            redirect to <span class="font-italic text-success font-weight-bold">*140#</span>
        </Alert>

        <Row :gutter="20" class="mt-4"> 

            <!-- Redirect Trigger Type -->
            <Col :span="12" class="d-flex">

                <span class="font-weight-bold text-dark mt-1 mr-2">Trigger:</span>

                <Select v-model="event.event_data.general.trigger.selected_type">
                    <Option v-for="(triggerType, index) in triggerTypes" :value="triggerType.value" :key="index">
                        {{ triggerType.name }}
                    </Option>
                </Select>

            </Col>

            <Col :span="24">
            
                <Row class="bg-grey-light py-2 px-4 mt-3"> 

                    <!-- Manual Trigger Input -->
                    <Col v-if="event.event_data.general.trigger.selected_type == 'manual'" :span="24" class="mt-2">

                        <!-- Manual Input --> 
                        <textOrCodeEditor
                            size="small"
                            title="Input"
                            placeholder="3"
                            class="mt-2 mb-2"
                            :value="event.event_data.general.trigger.manual.input"
                            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                        </textOrCodeEditor>

                    </Col>

                    <!-- Service Code -->
                    <Col :span="24" class="mt-2">

                        <!-- Service Code Input --> 
                        <textOrCodeEditor
                            size="small"
                            class="mt-2 mb-2"
                            title="Service Code"
                            :placeholder="'*140#'"
                            :value="event.event_data.service_code"
                            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                        </textOrCodeEditor>

                    </Col>

                </Row>

            </Col>

        </Row>

    </div>

</template>

<script>

    import textOrCodeEditor from './../../../textOrCodeEditor.vue';
    var localCustomMixin = require('./localMixin.js').default;

    export default {
        components: { textOrCodeEditor },
        mixins: [localCustomMixin],
        props:{
            event: {
                type: Object,
                default:() => {}
            },
            events: {
                type: Array,
                default: () => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return{
                /** Note: The "revisitTypes" and "triggerTypes" properties are
                 *  defined within the "localCustomMixin"
                 * 
                */
            }
        }
    }
</script>