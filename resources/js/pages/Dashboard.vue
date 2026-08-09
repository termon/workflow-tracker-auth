<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    Copy,
    Ellipsis,
    FolderKanban,
    Pencil,
    Plus,
    RotateCcw,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

interface Workflow {
    id: number;
    name: string;
    description: string | null;
    archived_at: string | null;
    deleted_at: string | null;
    created_at: string;
}

const props = defineProps<{
    activeWorkflows: Workflow[];
    archivedWorkflows: Workflow[];
    recycledWorkflows: Workflow[];
    flash: { status: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Workflows', href: dashboard() }],
    },
});

const createOpen = ref(false);
const renameOpen = ref(false);
const deleteOpen = ref(false);
const selectedWorkflow = ref<Workflow | null>(null);

const createForm = useForm({ name: '', description: '' });
const renameForm = useForm({ name: '' });

function submitCreate(): void {
    createForm.post('/workflows', {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
}

function beginRename(workflow: Workflow): void {
    selectedWorkflow.value = workflow;
    renameForm.name = workflow.name;
    renameForm.clearErrors();
    renameOpen.value = true;
}

function submitRename(): void {
    if (!selectedWorkflow.value) {
        return;
    }

    renameForm.patch(`/workflows/${selectedWorkflow.value.id}/rename`, {
        preserveScroll: true,
        onSuccess: () => {
            renameOpen.value = false;
            selectedWorkflow.value = null;
        },
    });
}

function beginDelete(workflow: Workflow): void {
    selectedWorkflow.value = workflow;
    deleteOpen.value = true;
}

function deleteWorkflow(): void {
    if (!selectedWorkflow.value) {
        return;
    }

    router.delete(`/workflows/${selectedWorkflow.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false;
            selectedWorkflow.value = null;
        },
    });
}

function postAction(workflow: Workflow, action: string): void {
    router.post(
        `/workflows/${workflow.id}/${action}`,
        {},
        { preserveScroll: true },
    );
}

function permanentlyDelete(workflow: Workflow): void {
    if (
        !window.confirm(
            `Permanently delete “${workflow.name}”? This cannot be undone.`,
        )
    ) {
        return;
    }

    router.delete(`/workflows/${workflow.id}/force`, { preserveScroll: true });
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(
        new Date(value),
    );
}
</script>

<template>
    <Head title="Workflows" />

    <div class="flex h-full flex-1 flex-col gap-8 overflow-x-hidden p-4 md:p-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="max-w-2xl">
                <h1 class="text-2xl font-semibold tracking-tight">Workflows</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create and track structured workflows from one place.
                </p>
            </div>
            <Button class="w-full sm:w-auto" @click="createOpen = true">
                <Plus class="size-4" />
                Create workflow
            </Button>
        </header>

        <div
            v-if="props.flash.status"
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
            role="status"
        >
            {{ props.flash.status }}
        </div>

        <section aria-labelledby="active-workflows-heading">
            <h2 id="active-workflows-heading" class="sr-only">
                Active workflows
            </h2>

            <Card v-if="activeWorkflows.length === 0" class="border-dashed">
                <CardContent
                    class="flex min-h-72 flex-col items-center justify-center gap-4 text-center"
                >
                    <div
                        class="grid size-12 place-items-center rounded-full bg-muted"
                    >
                        <FolderKanban class="size-6 text-muted-foreground" />
                    </div>
                    <div class="max-w-md">
                        <h3 class="font-semibold">No active workflows</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Create a workflow to begin building its structure.
                        </p>
                    </div>
                    <Button variant="outline" @click="createOpen = true">
                        <Plus class="size-4" />
                        Create your first workflow
                    </Button>
                </CardContent>
            </Card>

            <div v-else class="grid gap-5 lg:grid-cols-2 2xl:grid-cols-3">
                <Card
                    v-for="workflow in activeWorkflows"
                    :key="workflow.id"
                    class="group"
                >
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-4"
                    >
                        <div class="min-w-0">
                            <CardTitle class="truncate">{{
                                workflow.name
                            }}</CardTitle>
                            <CardDescription class="mt-2 line-clamp-2 min-h-10">
                                {{
                                    workflow.description ||
                                    'No description yet.'
                                }}
                            </CardDescription>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    :aria-label="`Actions for ${workflow.name}`"
                                >
                                    <Ellipsis class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem
                                    @select="beginRename(workflow)"
                                >
                                    <Pencil class="size-4" /> Rename
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @select="postAction(workflow, 'duplicate')"
                                >
                                    <Copy class="size-4" /> Duplicate
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @select="postAction(workflow, 'archive')"
                                >
                                    <Archive class="size-4" /> Archive
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    class="text-destructive focus:text-destructive"
                                    @select="beginDelete(workflow)"
                                >
                                    <Trash2 class="size-4" /> Move to recycle
                                    bin
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Created {{ formatDate(workflow.created_at) }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"
                        ><Archive class="size-4" /> Archived</CardTitle
                    >
                    <CardDescription
                        >Workflows kept out of your active
                        workspace.</CardDescription
                    >
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <p
                        v-if="archivedWorkflows.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No archived workflows.
                    </p>
                    <div
                        v-for="workflow in archivedWorkflows"
                        :key="workflow.id"
                        class="flex items-center justify-between gap-4 rounded-lg border p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ workflow.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Archived {{ formatDate(workflow.archived_at!) }}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="postAction(workflow, 'unarchive')"
                        >
                            <ArchiveRestore class="size-4" /> Restore
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"
                        ><Trash2 class="size-4" /> Recycle bin</CardTitle
                    >
                    <CardDescription
                        >Deleted workflows can be restored or permanently
                        removed.</CardDescription
                    >
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <p
                        v-if="recycledWorkflows.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        The recycle bin is empty.
                    </p>
                    <div
                        v-for="workflow in recycledWorkflows"
                        :key="workflow.id"
                        class="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ workflow.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Deleted {{ formatDate(workflow.deleted_at!) }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="postAction(workflow, 'restore')"
                            >
                                <RotateCcw class="size-4" /> Restore
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="permanentlyDelete(workflow)"
                            >
                                Delete forever
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>

    <Dialog v-model:open="createOpen">
        <DialogContent class="sm:max-w-lg">
            <form class="flex flex-col gap-6" @submit.prevent="submitCreate">
                <DialogHeader>
                    <DialogTitle>Create workflow</DialogTitle>
                    <DialogDescription
                        >Start with an empty root container. You can add
                        structure next.</DialogDescription
                    >
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="workflow-name">Name</Label>
                    <Input
                        id="workflow-name"
                        v-model="createForm.name"
                        autofocus
                        :aria-invalid="Boolean(createForm.errors.name)"
                    />
                    <InputError :message="createForm.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="workflow-description">Description</Label>
                    <textarea
                        id="workflow-description"
                        v-model="createForm.description"
                        rows="4"
                        class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    />
                    <InputError :message="createForm.errors.description" />
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="createOpen = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="createForm.processing"
                        >Create workflow</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="renameOpen">
        <DialogContent class="sm:max-w-md">
            <form class="flex flex-col gap-6" @submit.prevent="submitRename">
                <DialogHeader>
                    <DialogTitle>Rename workflow</DialogTitle>
                    <DialogDescription
                        >Choose a clear name for this
                        workflow.</DialogDescription
                    >
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="rename-workflow">Name</Label>
                    <Input
                        id="rename-workflow"
                        v-model="renameForm.name"
                        autofocus
                        :aria-invalid="Boolean(renameForm.errors.name)"
                    />
                    <InputError :message="renameForm.errors.name" />
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="renameOpen = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="renameForm.processing"
                        >Save</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Move workflow to recycle bin?</DialogTitle>
                <DialogDescription>
                    {{ selectedWorkflow?.name }} can be recovered later from the
                    recycle bin.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deleteOpen = false"
                    >Cancel</Button
                >
                <Button variant="destructive" @click="deleteWorkflow"
                    >Move to recycle bin</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
