<template>

    <Row :gutter="10">

        <Col :span="activeView == 'Debugger' ? 12 : 24">

            <Card>
                
                <debuggerWidget v-if="activeView == 'Debugger'"  v-bind="$props" :ussdSimulatorLoading="ussdSimulatorLoading" :ussdSimulatorResponse="ussdSimulatorResponse"></debuggerWidget>
                
                <userAccountsWidget v-if="activeView == 'User Accounts'"  v-bind="$props" :ussdSimulatorLoading="ussdSimulatorLoading"></userAccountsWidget>
                
                <settingsWidget v-if="activeView == 'Settings'"  v-bind="$props"></settingsWidget>
                
            </Card>

        </Col>

        <!-- USSD Simulator -->
        <Col v-show="activeView == 'Debugger'" :span="12">
        
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
    import lineChart from './../../../../../../../../components/_common/charts/lineChart.vue';
    import userAccountsWidget from './user-accounts/main.vue';
    import debuggerWidget from './debugger/main.vue';    
    import settingsWidget from './settings/main.vue';

    export default {
        components: { ussdSimulator, userAccountsWidget, debuggerWidget, settingsWidget },
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
        },
        watch: {
            //  Watch for changes on the ussdSimulatorLoading
            ussdSimulatorLoading: {
                handler: function (newVal, oldVal) {
                    this.$emit('ussdSimulatorLoading', newVal);
                }
            },
            //  Watch for changes on the ussdSimulatorResponse
            ussdSimulatorResponse: {
                handler: function (newVal, oldVal) {
                    this.$emit('ussdSimulatorResponse', newVal);
                }
            }  
        },
    };
  
</script>