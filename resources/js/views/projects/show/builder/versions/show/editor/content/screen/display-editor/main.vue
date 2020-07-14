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

            <span class="d-block mt-4 mb-4">
                <span class="font-weight-bold mr-1">Conditional Selection:</span>
                <i-Switch v-model="screen.conditional_displays.active" />
            </span>

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
                        :list="screen.displays"
                        @start="drag=true" 
                        @end="drag=false" 
                        :options="{
                            group:'screen-displays', 
                            handle:'.dragger-handle',
                            draggable:'.single-draggable-item'
                        }">

                        <!-- Single display menu  -->
                        <singleDisplay v-for="(display, index) in screen.displays" :key="index"
                            :globalMarkers="globalMarkers"
                            :builder="builder"
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
                :builder="builder"
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
            builder: {
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
                isOpenAddDisplayModal: false
            }
        },
        computed: {
            displaysExist(){
                return this.screen.displays.length ? true : false;
            },
            addButtonType(){
                return this.displaysExist ? 'primary' : 'success';
            }
        },
        methods: {
            handleOpenAddDisplayModal(index) {
                this.isOpenAddDisplayModal = true;
            },
        },
    }

</script>
