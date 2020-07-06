<template>

    <Row :gutter="12">

        <Col :span="7">
            
            <simulatorAside 
                @showDebugger="handleShowDebugger()"
                @showSettings="handleShowSettings()"
                @handleShowSubscriptions="handlehandleShowSubscriptions()">
            </simulatorAside>

        </Col>

        <Col :span="17">
        
            <template v-if="activeView == 'Debugger'">

                <simulatorDebugger :version="version" :project="project"></simulatorDebugger>

            </template>

            <template v-else-if="activeView == 'Subscriptions'">
                
                //  Subscription settings

            </template>

            <template v-else-if="activeView == 'Settings'">
                
                //  Subscription settings

            </template>

        </Col>

    </Row>

</template>

<script>

    import simulatorDebugger from './content/main.vue';
    import simulatorAside from './aside/main.vue';

    export default {
        components: { simulatorDebugger, simulatorAside },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                activeView: 'Debugger',
                availableViews: ['Debugger', 'Subscriptions', 'Settings'],
            }
        },
        methods: {
            handleShowDebugger(){

                //  Set "Debugger" as the active viewport
                this.handleChangeView('Debugger');

            },
            handleShowSettings(){

                //  Set "Subscriptions" as the active viewport
                this.handleChangeView('Subscriptions');

            },
            handleShowSubscriptions(){

                //  Set "Settings" as the active viewport
                this.handleChangeView('Settings');

            },
            handleChangeView(name){
                //  If a viewport with the given name exists
                if( this.availableViews.includes( name ) ){

                    //  Set the active viewport to the given name
                    this.activeView = name;
                }
            },
        },
        created(){

            //  Show the "Debugger" viewport
            this.handleShowDebugger();
        }
    }
</script>
