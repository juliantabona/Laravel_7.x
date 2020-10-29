<template>

    <div>

        <!-- Linking Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Linking</span> to go link to a specific
            <span class="font-italic text-success font-weight-bold">Screen</span> automatically or using a trigger
        </Alert>

        <Row :gutter="20" class="mt-4"> 

            <!-- Linking Trigger Type -->
            <Col :span="firstRowSpan" class="d-flex mb-2">

                <span class="font-weight-bold text-dark mt-1 mr-2">Trigger:</span>

                <Select v-model="event.event_data.trigger.selected_type">
                    <Option v-for="(triggerType, index) in triggerTypes" :value="triggerType.value" :key="index">
                        {{ triggerType.name }}
                    </Option>
                </Select>

            </Col>

            <Col :span="firstRowSpan" class="mb-2">

                <!-- Select Screen/Display Link -->
                <screenAndDisplaySelector 
                    :link="event.event_data.link"
                    :version="version" :screen="screen" :display="display" :showDisplays="true">
                </screenAndDisplaySelector>
            
            </Col>

            <Col v-if="event.event_data.trigger.selected_type == 'manual'" :span="24">

                <!-- Manul Input --> 
                <textOrCodeEditor
                    size="small"
                    title="Input"
                    placeholder="3"
                    class="mt-2 mb-2"
                    :value="event.event_data.trigger.manual.input"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                </textOrCodeEditor>

            </Col>

        </Row>

    </div>

</template>

<script>

    import screenAndDisplaySelector from './../../../screenAndDisplaySelector.vue';
    import textOrCodeEditor from './../../../textOrCodeEditor.vue';

    export default {
        components: { screenAndDisplaySelector, textOrCodeEditor },
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
            version: {
                type: Object,
                default: () => {}
            }
        },
        data(){
            return{
                triggerTypes: [
                    {
                        name: 'Automatic',
                        value: 'automatic'
                    },
                    {
                        name: 'Manual',
                        value: 'manual'
                    }
                ]
            }
        },
        computed: {
            firstRowSpan(){

                if( this.event.event_data.link.code_editor_mode ){

                    return 24;

                }

                return 12;

            }
        },
    }
</script>