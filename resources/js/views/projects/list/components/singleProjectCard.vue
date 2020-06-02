<template>

    <Card class="cursor-pointer">
        
        <Row>

            <Col :span="22">

                <div class="d-flex">

                    <!-- Project Logo -->
                    <Icon type="logo-github" :size="34" />

                    <!-- Project Name -->
                    <span class="font-weight-bold mt-2 ml-2">{{ project.name }}</span>

                </div>

            </Col>

            <Col :span="2">

                <!-- Project Menu -->
                <Dropdown trigger="click" class="mt-1">
                    <Icon type="md-more" :size="24" />
                    <DropdownMenu slot="list">
                        <DropdownItem>Edit</DropdownItem>
                        <DropdownItem>Delete</DropdownItem>
                        <DropdownItem>Duplicate</DropdownItem>
                    </DropdownMenu>
                </Dropdown>

            </Col>

        </Row>

    </Card>

</template>

<script>
    export default {
        props: {
            project: {
                type: Object,
                default: null
            }
        }
    }
</script>
