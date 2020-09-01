<template>

    <Row :gutter="10">

        <Col :span="12">

            <Card>
                
                <debuggerWidget v-if="activeView == 'Debugger'"  v-bind="$props" :ussdSimulatorLoading="ussdSimulatorLoading" :ussdSimulatorResponse="ussdSimulatorResponse"></debuggerWidget>
                
                <settingsWidget v-if="activeView == 'Settings'"  v-bind="$props" :ussdSimulatorLoading="ussdSimulatorLoading" :ussdSimulatorResponse="ussdSimulatorResponse"></settingsWidget>
                
            </Card>

        </Col>

        <!-- USSD Simulator -->
        <Col :span="12">
        
            <ussdSimulator 
                :project="project"
                :version="version"
                @loading="ussdSimulatorLoading = $event"
                @response="ussdSimulatorResponse = $event">
            </ussdSimulator>
            
        </Col>

    </Row>
    
</template>

<script>

    import ussdSimulator from './../../../../../../../../components/_common/simulators/ussdSimulator.vue';
    import debuggerWidget from './debugger.vue';
    import settingsWidget from './settings.vue';

    export default {
        components: { ussdSimulator, debuggerWidget, settingsWidget },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            activeView: {
                type: String,
                default: ''
            },
        },
        data(){
            return {
                ussdSimulatorLoading: false,
                ussdSimulatorResponse: null
            }
        }
    };
  
</script>