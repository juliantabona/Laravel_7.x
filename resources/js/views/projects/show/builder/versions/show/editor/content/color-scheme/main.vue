<template>

    <Card :bordered="false" :style="{ overflow: 'inherit' }">

        <!-- Main Heading -->
        <Divider orientation="left">
            <span class="font-weight-bold text-dark">Event Color Scheme</span>
        </Divider>

        <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">

            <Row>

                <Col v-for="(hex_color, type) in version.builder.color_scheme.event_colors" :key="type" :span="8" class="mb-2">

                    <div class="clearfix">

                        <span :class="['float-left', 'mt-2']">

                            <!-- Event Icon -->
                            <eventIcon :eventType="type" :size="20"></eventIcon>

                            <!-- Event Type -->
                            <span class="mr-2">{{ type }}</span>

                        </span>

                        <!-- Event Color -->
                        <ColorPicker v-model="version.builder.color_scheme.event_colors[type]" recommend :class="['float-right', 'mr-5']"></ColorPicker>

                    </div>

                </Col>

            </Row>

        </div>

    </Card>

</template>

<script>

    import basicButton from './../../../../../../../../../components/_common/buttons/basicButton.vue';
    import eventIcon from './../screen/event-editor/eventIcon.vue';

    export default {
        components: { basicButton, eventIcon },
        props: {
            version: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
            }
        }
    }
</script>
