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

        <Col :span="24">

            <div :style="{ overflow: 'scroll' }" class="d-flex py-5">

                <div :style="{ width: '350px' }" class="border-right mx-1 px-1">

                    <div :style="{ width: '300px' }" class="bg-white border m-auto pb-3 px-4">
                    
<p style="white-space: pre-wrap;">
Neo Boutique
1. General
2. Orders (0)
3. Products (1)
4. Customers (0)
5. Coupons (0)
6. Staff (0)
7. Reviews (0)
0. Back
</p>
                    
                    </div>

                </div>

            </div>

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
