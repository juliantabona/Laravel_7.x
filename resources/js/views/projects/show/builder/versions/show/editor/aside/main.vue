<template>

    <div>

        <Card :bordered="false" class="mb-1">

            <!-- Version -->    
            <div class="d-flex">

                <span class="d-block mt-2 font-weight-bold text-dark mr-2">Select: </span>

                <Select v-model="selected_subcription_plan" filterable placeholder="Select version" class="mr-2">

                    <Option v-for="(subcription_plan, key) in subcription_plans" :key="key"
                            :value="subcription_plan.name" :label="subcription_plan.name">
                    </Option>

                </Select>

                <!-- Add Version Button -->
                <basicButton type="default" size="default" icon="ios-add" :showIcon="true"
                             buttonClass="p-1">
                </basicButton>

            </div>
            
        </Card>

        <Card :bordered="false">

            <div slot="title">
                
                <!-- Screen Menu Heading -->    
                <div class="clearfix pb-2">

                    <span class="d-block mt-2 font-weight-bold text-dark float-left">Screens</span>

                    <!-- Create Screen Button -->
                    <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                                 class="float-right" buttonClass="p-1" :ripple="!screensExist"
                                 @click.native="handleOpenAddScreenModal()">
                    </basicButton>

                </div>

                <!-- Draggable screen menus -->
                <div class="screen-menu-container border-top py-2">

                    <!-- If we have screens -->
                    <template v-if="screensExist">
                        
                        <draggable 
                            class="ussd-builder-screen-menus"
                            :list="builder.screens"
                            @start="drag=true" 
                            @end="drag=false" 
                            :options="{
                                group:'screen-menus', 
                                handle:'.dragger-handle',
                                draggable:'.screen-menu-item'
                            }">

                            <!-- Single Screen Menu  -->
                            <singleScreenMenu v-for="(currentScreen, index) in builder.screens" :key="index"   
                                :index="index"
                                :builder="builder"
                                :activeScreen="screen"
                                :screen="currentScreen"
                                @showScreens="$emit('showScreens')"
                                @selectedScreen="handleSelectedScreen($event)">
                            </singleScreenMenu>

                        </draggable>
                        
                    </template>

                    <!-- No Screens Alert -->
                    <Alert v-else type="info" class="mr-2 mb-0">No screens found</Alert>

                </div>

            </div>

            <CellGroup>
                <Cell title="Conditional Screens" @click.native="$emit('showConditionalScreens')">
                    <i-Switch v-model="builder.conditional_screens.active" slot="extra" />
                </Cell>
                <Cell title="Subscription Plans" @click.native="$emit('showSubscriptions')"/>
                <Cell title="Global Variables" @click.native="$emit('showGlobalVariables')"/>
            </CellGroup>
            
        </Card>

        <!-- 
            MODAL TO ADD NEW SCREEN
        -->
        <template v-if="isOpenAddScreenModal">

            <addScreenModal
                :builder="builder"
                @selectedScreen="handleSelectedScreen($event)"
                @visibility="isOpenAddScreenModal = $event">
            </addScreenModal>

        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';

    import addScreenModal from './addScreenModal.vue';

    import singleScreenMenu from './single-screen-menu/main.vue';

    import basicButton from './../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, addScreenModal, singleScreenMenu, basicButton },
        props: {
            screen: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
        },
        data(){
            return {
                isOpenAddScreenModal: false,
                subcription_plans: [
                    {
                        name: 'Subcription Plan 1',
                    },
                    {
                        name: 'Subcription Plan 2',
                    },
                    {
                        name: 'Subcription Plan 3',
                    }
                ],
                selected_subcription_plan: 'Subcription Plan 1'
            }
        },
        computed: {
            screensExist(){
                return this.builder.screens.length ? true : false;
            },
            addButtonType(){
                return this.screensExist ? 'default' : 'success';
            }
        },
        methods: {
            handleOpenAddScreenModal(index) {
                this.isOpenAddScreenModal = true;
            },
            handleSelectedScreen(index){

                this.$emit('selectedScreen', index);

            }
        }
    }
</script>
