<template>

    <Row :gutter="12">

        <Col :span="7">
            
            <simulatorAside 
                @showDebugger="handleShowDebugger()"
                @showSettings="handleShowSettings()"
                @showUserAccounts="handleShowUserAccounts()">
            </simulatorAside>

        </Col>

        <Col :span="17">

            <simulatorContent :version="version" :project="project" :activeView="activeView"></simulatorContent>

        </Col>

    </Row>

</template>

<script>

    import simulatorContent from './content/main.vue';
    import simulatorAside from './aside/main.vue';

    export default {
        components: { simulatorContent, simulatorAside },
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
                availableViews: ['Debugger', 'User Accounts', 'Settings'],
            }
        },
        methods: {
            handleShowDebugger(){

                //  Set "Debugger" as the active viewport
                this.handleChangeView('Debugger');

            },
            handleShowSettings(){

                //  Set "Settings" as the active viewport
                this.handleChangeView('Settings');

            },
            handleShowUserAccounts(){

                //  Set "User Accounts" as the active viewport
                this.handleChangeView('User Accounts');

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
