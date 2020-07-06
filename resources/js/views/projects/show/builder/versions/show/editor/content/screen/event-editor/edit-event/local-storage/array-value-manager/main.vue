<template>

    <div>

        <!-- Array Value List & Dragger  -->
        <draggable
            :list="arrayValues"
            @start="drag=true"
            @end="drag=false"
            :options="{
                group:'array_values',
                handle:'.dragger-handle',
                draggable:'.single-draggable-item',
            }">

            <!-- Single Array Value  -->
            <singleArrayValue v-for="(arrayValue, index) in arrayValues" :key="arrayValue.id+'_'+index"
                :arrayValues="arrayValues"
                :arrayValue="arrayValue"
                :builder="builder"
                :display="display"
                :screen="screen"
                :index="index">
            </singleArrayValue>

            <!-- No array values message -->
            <Alert v-if="!arrayValuesExist" type="info" class="mb-0" style="width:300px;" show-icon>No Array Values Found</Alert>

        </draggable>

        <div class="clearfix">

            <!-- Add Array Value Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                            class="float-right" :ripple="!arrayValuesExist"
                            @click.native="handleAddArrayValue()">
                <span>Add Value</span>
            </basicButton>

        </div>

        <!-- 
            MODAL TO ADD ARRAY VALUE
        -->
        <template v-if="isOpenManageArrayValueModal">

            <manageArrayValueModal
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="false"
                :isEditing="false"
                :arrayValues="arrayValues"
                @visibility="isOpenManageArrayValueModal = $event">
            </manageArrayValueModal>
    
        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';
    import singleArrayValue from './single-array-value/main.vue';
    import manageArrayValueModal from './manageArrayValueModal.vue';
    import basicButton from './../../../../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, singleArrayValue, manageArrayValueModal, basicButton },
        props: { 
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
            builder: {
                type: Object,
                default: () => {}
            },
        },
        data(){
            return {
                isOpenManageArrayValueModal: false,
                arrayValues: this.event.event_data.storage.array.dataset.values
            }
        },
        computed: {
            totalArrayValues(){
                return this.arrayValues.length;
            },
            arrayValuesExist(){
                return this.totalArrayValues ? true : false;
            },
            addButtonType(){
                return this.arrayValuesExist ? 'default' : 'success';
            }
        },
        methods: {
            handleAddArrayValue(){
                this.isOpenManageArrayValueModal = true;
            }
        }
    };
  
</script>