<template>

    <div>

        <!-- Revisit Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Revisit</span> to go back to the
            <span class="font-italic text-success font-weight-bold">Main Menu</span>, a
            <span class="font-italic text-success font-weight-bold">Named Screen</span> or a
            <span class="font-italic text-success font-weight-bold">Marked Screen</span>
            
        </Alert>

        <Row :gutter="20" class="mt-4"> 

            <!-- Revisit Type Selector -->
            <Col :span="12" class="d-flex">

                <span class="d-block font-weight-bold text-dark mt-1 mr-2">Type:</span>
                
                <Select v-model="event.event_data.revisit_type.selected_type">
                    <Option v-for="(revisitType, index) in revisitTypes" :value="revisitType.value" :key="index">
                        {{ revisitType.name }}
                    </Option>
                </Select>
            
            </Col>

            <!-- Revisit Trigger Type -->
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

                        <!-- Manul Input --> 
                        <textOrCodeEditor
                            size="small"
                            title="Input"
                            placeholder="3"
                            class="mt-2 mb-2"
                            :value="event.event_data.general.trigger.manual.input"
                            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                        </textOrCodeEditor>

                    </Col>

                    <!-- Revisit Screen Manager -->
                    <Col v-if="event.event_data.revisit_type.selected_type == 'screen_revisit'" :span="24" class="mt-2">

                        <revisitScreenManager v-bind="$props"></revisitScreenManager>

                    </Col>

                    <!-- Revisit Marker Manager -->
                    <Col v-if="event.event_data.revisit_type.selected_type == 'marked_revisit'" :span="24" class="mt-2">

                        <revisitMarkerManager v-bind="$props"></revisitMarkerManager>

                    </Col>

                    <!-- Automatic Replies -->
                    <Col :span="24" class="mt-2">

                        <!-- Automatic Replies Input --> 
                        <textOrCodeEditor
                            size="small"
                            class="mt-2 mb-2"
                            title="Automatic Replies"
                            :placeholder="'1*2*3*{{ order.id }}*4'"
                            :value="event.event_data.general.automatic_replies"
                            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                        </textOrCodeEditor>

                    </Col>

                </Row>

            </Col>

        </Row>

    </div>

</template>

<script>

    var localCustomMixin = require('./localMixin.js').default;

    import textOrCodeEditor from './../../../textOrCodeEditor.vue';
    import revisitMarkerManager from './revisit-marker-manager/main.vue';
    import revisitScreenManager from './revisit-screen-manager/main.vue';

    export default {
        components: { textOrCodeEditor, revisitMarkerManager, revisitScreenManager },
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