<template>

    <Row :gutter="20">

        <Col :span="24">

            <!--  Display Heading -->
            <div class="clearfix pb-2 mb-3 border-bottom">

                <span class="d-block mt-2 font-weight-bold text-dark float-left">Displays</span>

                <!-- Add Display Button -->
                <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                             class="float-right" iconDirection="left" :ripple="!displaysExist"
                             @click.native="handleOpenAddDisplayModal()">
                    <span>Add Display</span>
                </basicButton>

            </div>

            <Row :gutter="20">

                <Col :span="12">

                    <span class="align-items-center d-flex">
                        <Icon type="md-share" class="bg-grey-light border mr-2 p-1 rounded-circle" size="20" />
                        <span class="font-weight-bold mr-1">Conditional Display Selection:</span>
                        <i-Switch v-model="screen.conditional_displays.active" />
                    </span>

                </Col>

                <Col :span="12">
                
                    <Input 
                        type="text" v-model="searchTerm"  
                        prefix="ios-search" class="mb-2"
                        placeholder="Search by display name or id">
                    </Input>

                </Col>

            </Row>

            <template v-if="screen.conditional_displays.active">

                <!-- Code Editor -->
                <customEditor
                    :useCodeEditor="true"
                    :codeContent="screen.conditional_displays.code"
                    @codeChange="screen.conditional_displays.code = $event"
                    sampleCodeTemplate="ussd_service_instructions_sample_code">
                </customEditor>

            </template>

            <!-- Draggable displays -->
            <div class="py-2">

                <!-- If we have displays -->
                <template v-if="displaysExist">
                    
                    <draggable 
                        :list="filteredDisplays"
                        @start="drag=true" 
                        @end="drag=false" 
                        :options="{
                            group:'screen-displays', 
                            handle:'.dragger-handle',
                            draggable:'.single-draggable-item'
                        }">

                        <!-- Single display menu  -->
                        <singleDisplay v-for="(display, index) in filteredDisplays" :key="index"
                            :globalMarkers="globalMarkers"
                            :isFiltered="isFiltered"
                            :version="version"
                            :display="display"
                            :screen="screen"
                            :index="index">
                        </singleDisplay>

                    </draggable>

                </template>

                <!-- No Displays Alert -->
                <Alert v-else type="info" class="mr-2 mb-0">No displays found</Alert>

            </div>

        </Col>

        <!-- 
            MODAL TO ADD NEW DISPLAY
        -->
        <template v-if="isOpenAddDisplayModal">

            <addDisplayModal
                :screen="screen"
                :version="version"
                @visibility="isOpenAddDisplayModal = $event">
            </addDisplayModal>

        </template>

    </Row>

</template>

<script>

    import draggable from 'vuedraggable';
    import addDisplayModal from './addDisplayModal.vue';
    import singleDisplay from './single-display/main.vue';
    import basicButton from './../../../../../../../../../../components/_common/buttons/basicButton.vue';
    import customEditor from './../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { draggable, singleDisplay, addDisplayModal, basicButton, customEditor },
        props: {
            screen: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                searchTerm: '',
                isFiltered: false,
                isOpenAddDisplayModal: false,
            }
        },
        computed: {
            displaysExist(){
                return this.screen.displays.length ? true : false;
            },
            addButtonType(){
                return this.displaysExist ? 'primary' : 'success';
            },
            filteredDisplays(){

                if( this.searchTerm ){

                    this.isFiltered = true;

                    //  Return filtered displays
                    return this.screen.displays.filter((display, index) => {
                        
                        var searchTerm = this.searchTerm.trim().toLowerCase();
                        
                        var displayName = display.name.trim().toLowerCase();

                        //  Define the search pattern
                        var regex_pattern = RegExp(searchTerm,'g');

                        //  Search displays who's name or id matches with our search argument
                        if( (regex_pattern.test(displayName) || searchTerm == display.id) ){
                            return true;
                        }
                        
                        return false;
                    })

                }else{

                    this.isFiltered = false;

                    //  Return all displays
                    return this.screen.displays;
                    
                }
            }
        },
        methods: {
            handleOpenAddDisplayModal(index) {
                this.isOpenAddDisplayModal = true;
            },
        },
    }

</script>
