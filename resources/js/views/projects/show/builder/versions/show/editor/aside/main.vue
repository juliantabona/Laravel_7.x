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

                <Input
                    type="text" v-model="searchTerm"
                    prefix="ios-search" class="mb-2"
                    placeholder="Search by screen name or id">
                </Input>

                <!-- Draggable screen menus -->
                <div class="screen-menu-container border-top py-2">

                    <!-- If we have screens -->
                    <template v-if="screensExist">

                        <draggable
                            class="ussd-builder-screen-menus"
                            :list="filteredScreens"
                            @start="drag=true"
                            @end="drag=false"
                            :options="{
                                group:'screen-menus',
                                handle:'.dragger-handle',
                                draggable:'.screen-menu-item-wrapper'
                            }">

                            <div v-for="(currentScreen, index) in version.builder.screens" :key="index"
                                 class="screen-menu-item-wrapper">

                                <!-- Only show filtere screens -->
                                <template v-if="filteredScreenIds.includes(currentScreen.id)">

                                    <!-- Single Screen Menu  -->
                                    <singleScreenMenu
                                        :index="index"
                                        :version="version"
                                        :activeScreen="screen"
                                        :screen="currentScreen"
                                        @showScreens="$emit('showScreens')"
                                        @selectedScreen="handleSelectedScreen($event)">
                                    </singleScreenMenu>

                                </template>

                            </div>

                        </draggable>

                    </template>

                    <!-- No Screens Alert -->
                    <Alert v-else type="info" class="mr-2 mb-0">No screens found</Alert>

                </div>

            </div>

            <CellGroup>
                <Cell @click.native="$emit('showConditionalScreens')">
                    <span name="label" class="align-items-center d-flex" :style="{ marginLeft: '-10px' }">
                        <Icon type="md-share" class="bg-grey-light border mr-1 p-1 rounded-circle" size="16" />
                        <span class="mr-2">Conditional Screen Selection</span>
                        <i-Switch v-model="version.builder.conditional_screens.active" />
                    </span>
                </Cell>
                <Cell title="Subscription Plans" @click.native="$emit('showSubscriptions')"/>
                <Cell title="Global Variables" @click.native="$emit('showGlobalVariables')">
                    <Badge :count="totalGlobalVariables" type="info" slot="extra" />
                </Cell>
                <Cell title="Global Events" @click.native="$emit('showGlobalEvents')">
                    <Badge :count="totalGlobalEvents" type="info" slot="extra" />
                </Cell>
                <Cell title="Color Scheme" @click.native="$emit('showColorScheme')"/>
            </CellGroup>

        </Card>

        <!--
            MODAL TO ADD NEW SCREEN
        -->
        <template v-if="isOpenAddScreenModal">

            <addScreenModal
                :version="version"
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
            version: {
                type: Object,
                default: null
            },
        },
        data(){
            return {
                searchTerm: '',
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
            totalGlobalEvents(){
                return this.version.builder.global_events.length;
            },
            totalGlobalVariables(){
                return this.version.builder.global_variables.length;
            },
            filteredScreens(){

                if( this.searchTerm ){
                    //  Return filtered screens
                    return this.version.builder.screens.filter((screen, index) => {

                        var searchTerm = this.searchTerm.trim().toLowerCase();

                        var screenName = screen.name.trim().toLowerCase();

                        //  Define the search pattern
                        var regex_pattern = RegExp(searchTerm,'g');

                        //  Search screens who's name or id matches with our search argument
                        if( (regex_pattern.test(screenName) || searchTerm == screen.id) ){
                            return true;
                        }

                        return false;
                    })
                }else{
                    //  Return all screens
                    return this.version.builder.screens;
                }
            },
            filteredScreenIds(){
                return this.filteredScreens.map((screen) => {
                    return screen.id;
                });
            },
            screensExist(){
                return this.filteredScreens.length ? true : false;
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
