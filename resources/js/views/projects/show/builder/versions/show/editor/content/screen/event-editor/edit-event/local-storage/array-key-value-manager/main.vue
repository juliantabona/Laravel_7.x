<template>

    <div>

        <!-- Key/Value List & Dragger  -->
        <draggable
            :list="keyValues"
            @start="drag=true"
            @end="drag=false"
            :options="{
                group:'key_values',
                handle:'.dragger-handle',
                draggable:'.single-draggable-item',
            }">

            <!-- Single Key/Value  -->
            <singleKeyValue v-for="(keyValue, index) in keyValues" :key="keyValue.id+'_'+index"
                :keyValues="keyValues"
                :keyValue="keyValue"
                :builder="builder"
                :display="display"
                :screen="screen"
                :index="index">
            </singleKeyValue>

            <!-- No key/values message -->
            <Alert v-if="!keyValuesExist" type="info" class="mb-0" style="width:300px;" show-icon>No Key Values Found</Alert>

        </draggable>

        <div class="clearfix">

            <!-- Add Key/Value Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                            class="float-right" :ripple="!keyValuesExist"
                            @click.native="handleAddKeyValue()">
                <span>Add Key Value </span>
            </basicButton>

        </div>

        <!-- 
            MODAL TO ADD KEY/VALUE
        -->
        <template v-if="isOpenManageKeyValueModal">

            <manageKeyValueModal
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="false"
                :isEditing="false"
                :keyValues="keyValues"
                @visibility="isOpenManageKeyValueModal = $event">
            </manageKeyValueModal>
    
        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';
    import singleKeyValue from './single-key-value/main.vue';
    import manageKeyValueModal from './manageKeyValueModal.vue';
    import basicButton from './../../../../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, singleKeyValue, manageKeyValueModal, basicButton },
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
                isOpenManageKeyValueModal: false,
                keyValues: this.event.event_data.storage.array.dataset.key_values
            }
        },
        computed: {
            totalKeyValues(){
                return this.keyValues.length;
            },
            keyValuesExist(){
                return this.totalKeyValues ? true : false;
            },
            addButtonType(){
                return this.keyValuesExist ? 'default' : 'success';
            }
        },
        methods: {
            handleAddKeyValue(){
                this.isOpenManageKeyValueModal = true;
            }
        }
    };
  
</script>