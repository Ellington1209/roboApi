<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRobotRequest;
use App\Http\Requests\UpdateRobotRequest;
use App\Models\Robot;
use App\Models\RobotFile;
use App\Models\RobotImage;
use App\Models\RobotParameter;
use App\Models\RobotVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class RobotController
{
    /**
     * Lista todos os robôs do usuário autenticado.
     * Super admin pode ver todos os robôs.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Robot::with(['parameters', 'images', 'files', 'user:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Se não for super admin, filtra apenas os robôs do usuário
        if (!$user->is_super_admin) {
            $query->where('user_id', $user->id);
        }

        // Filtros opcionais
        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $robots = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $robots->items(),
            'meta' => [
                'current_page' => $robots->currentPage(),
                'last_page' => $robots->lastPage(),
                'per_page' => $robots->perPage(),
                'total' => $robots->total(),
            ],
        ]);
    }

    /**
     * Cria um novo robô com parâmetros, imagens e versão.
     */
    public function store(StoreRobotRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();

            // Criar o robô
            $robot = Robot::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'language' => $request->language,
                'tags' => $request->tags ?? [],
                'code' => $request->code,
                'is_active' => $request->boolean('is_active', true),
                'version' => 1,
            ]);

            // Criar parâmetros
            if ($request->has('parameters') && is_array($request->parameters)) {
                foreach ($request->parameters as $param) {
                    RobotParameter::create([
                        'robot_id' => $robot->id,
                        'key' => $param['key'],
                        'label' => $param['label'],
                        'type' => $param['type'],
                        'value' => $param['value'],
                        'default_value' => $param['default_value'] ?? null,
                        'required' => $param['required'] ?? false,
                        'options' => $param['options'] ?? null,
                        'validation_rules' => $param['validation_rules'] ?? null,
                        'group' => $param['group'] ?? null,
                        'sort_order' => $param['sort_order'] ?? 0,
                    ]);
                }
            }

            // Upload de imagens
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $titles = $request->image_titles ?? [];
                $captions = $request->image_captions ?? [];

                foreach ($images as $index => $image) {
                    $path = $image->store("robots/{$robot->id}", 'public');
                    $fullPath = Storage::disk('public')->path($path);

                    // Obter dimensões da imagem
                    $imageInfo = getimagesize($fullPath);
                    $width = $imageInfo[0] ?? null;
                    $height = $imageInfo[1] ?? null;

                    $imageUrl = url(Storage::url($path));

                    RobotImage::create([
                        'robot_id' => $robot->id,
                        'title' => $titles[$index] ?? null,
                        'caption' => $captions[$index] ?? null,
                        'disk' => 'public',
                        'path' => $path,
                        'url' => $imageUrl,
                        'mime_type' => $image->getMimeType(),
                        'size_bytes' => $image->getSize(),
                        'width' => $width,
                        'height' => $height,
                        'is_primary' => $index === 0, // Primeira imagem é primary
                        'sort_order' => $index,
                    ]);
                }
            }

            // Upload de arquivos (psf, mq5)
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $fileNames = $request->file_names ?? [];

                foreach ($files as $index => $file) {
                    $path = $file->store("robots/{$robot->id}/files", 'public');
                    $fileUrl = url(Storage::url($path));
                    
                    // Determinar o tipo de arquivo pela extensão
                    $extension = strtolower($file->getClientOriginalExtension());
                    $fileType = in_array($extension, ['psf', 'mq5']) ? $extension : null;

                    RobotFile::create([
                        'robot_id' => $robot->id,
                        'name' => $fileNames[$index] ?? $file->getClientOriginalName(),
                        'disk' => 'public',
                        'path' => $path,
                        'url' => $fileUrl,
                        'mime_type' => $file->getMimeType(),
                        'file_type' => $fileType,
                        'size_bytes' => $file->getSize(),
                        'sort_order' => $index,
                    ]);
                }
            }

            // Criar versão inicial
            RobotVersion::create([
                'robot_id' => $robot->id,
                'version' => 1,
                'code' => $request->code,
                'changelog' => 'Versão inicial',
                'is_current' => true,
                'created_by' => $user->id,
            ]);

            DB::commit();

            // Carregar relacionamentos
            $robot->load(['parameters', 'images', 'files', 'user:id,name,email']);

            return response()->json([
                'message' => 'Robô criado com sucesso',
                'data' => $robot,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao criar robô',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe um robô específico.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = Robot::with(['parameters', 'images', 'files', 'versions', 'user:id,name,email']);

        // Se não for super admin, filtra apenas os robôs do usuário
        if (!$user->is_super_admin) {
            $query->where('user_id', $user->id);
        }

        $robot = $query->findOrFail($id);

        return response()->json([
            'data' => $robot,
        ]);
    }

    /**
     * Atualiza um robô existente.
     */
    public function update(UpdateRobotRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = Robot::query();
        if (!$user->is_super_admin) {
            $query->where('user_id', $user->id);
        }

        $robot = $query->findOrFail($id);

        try {
            DB::beginTransaction();

            $codeChanged = $request->has('code') && $request->code !== $robot->code;
            $shouldCreateVersion = $codeChanged && $request->boolean('create_version', false);

            // Atualizar robô
            $updateData = [];
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('language')) $updateData['language'] = $request->language;
            if ($request->has('tags')) $updateData['tags'] = $request->tags;
            if ($request->has('code')) $updateData['code'] = $request->code;
            if ($request->has('is_active')) $updateData['is_active'] = $request->boolean('is_active');

            if (!empty($updateData)) {
                $robot->update($updateData);
            }

            // Atualizar versão do robô se código mudou
            if ($codeChanged) {
                $robot->increment('version');
            }

            // Gerenciar parâmetros
            if ($request->has('parameters')) {
                // IDs dos parâmetros que devem ser mantidos
                $parameterIds = collect($request->parameters)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Deletar parâmetros que não estão na lista
                RobotParameter::where('robot_id', $robot->id)
                    ->whereNotIn('id', $parameterIds)
                    ->delete();

                // Atualizar ou criar parâmetros
                foreach ($request->parameters as $param) {
                    if (isset($param['id'])) {
                        // Atualizar existente
                        RobotParameter::where('id', $param['id'])
                            ->where('robot_id', $robot->id)
                            ->update([
                                'key' => $param['key'],
                                'label' => $param['label'],
                                'type' => $param['type'],
                                'value' => $param['value'],
                                'default_value' => $param['default_value'] ?? null,
                                'required' => $param['required'] ?? false,
                                'options' => $param['options'] ?? null,
                                'validation_rules' => $param['validation_rules'] ?? null,
                                'group' => $param['group'] ?? null,
                                'sort_order' => $param['sort_order'] ?? 0,
                            ]);
                    } else {
                        // Criar novo
                        RobotParameter::create([
                            'robot_id' => $robot->id,
                            'key' => $param['key'],
                            'label' => $param['label'],
                            'type' => $param['type'],
                            'value' => $param['value'],
                            'default_value' => $param['default_value'] ?? null,
                            'required' => $param['required'] ?? false,
                            'options' => $param['options'] ?? null,
                            'validation_rules' => $param['validation_rules'] ?? null,
                            'group' => $param['group'] ?? null,
                            'sort_order' => $param['sort_order'] ?? 0,
                        ]);
                    }
                }
            }

            // Deletar imagens
            if ($request->has('delete_image_ids')) {
                $imagesToDelete = RobotImage::whereIn('id', $request->delete_image_ids)
                    ->where('robot_id', $robot->id)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    Storage::disk($image->disk)->delete($image->path);
                    if ($image->thumbnail_path) {
                        Storage::disk($image->disk)->delete($image->thumbnail_path);
                    }
                    $image->delete();
                }
            }

            // Deletar arquivos
            if ($request->has('delete_file_ids')) {
                $filesToDelete = RobotFile::whereIn('id', $request->delete_file_ids)
                    ->where('robot_id', $robot->id)
                    ->get();

                foreach ($filesToDelete as $file) {
                    Storage::disk($file->disk)->delete($file->path);
                    $file->delete();
                }
            }

            // Upload de novas imagens
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $titles = $request->image_titles ?? [];
                $captions = $request->image_captions ?? [];

                $currentMaxSort = RobotImage::where('robot_id', $robot->id)->max('sort_order') ?? -1;

                foreach ($images as $index => $image) {
                    $path = $image->store("robots/{$robot->id}", 'public');
                    $fullPath = Storage::disk('public')->path($path);

                    $imageInfo = getimagesize($fullPath);
                    $width = $imageInfo[0] ?? null;
                    $height = $imageInfo[1] ?? null;

                    $imageUrl = url(Storage::url($path));

                    RobotImage::create([
                        'robot_id' => $robot->id,
                        'title' => $titles[$index] ?? null,
                        'caption' => $captions[$index] ?? null,
                        'disk' => 'public',
                        'path' => $path,
                        'url' => $imageUrl,
                        'mime_type' => $image->getMimeType(),
                        'size_bytes' => $image->getSize(),
                        'width' => $width,
                        'height' => $height,
                        'is_primary' => false,
                        'sort_order' => $currentMaxSort + $index + 1,
                    ]);
                }
            }

            // Upload de novos arquivos (psf, mq5)
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $fileNames = $request->file_names ?? [];
                
                $currentMaxSort = RobotFile::where('robot_id', $robot->id)->max('sort_order') ?? -1;

                foreach ($files as $index => $file) {
                    $path = $file->store("robots/{$robot->id}/files", 'public');
                    $fileUrl = url(Storage::url($path));
                    
                    // Determinar o tipo de arquivo pela extensão
                    $extension = strtolower($file->getClientOriginalExtension());
                    $fileType = in_array($extension, ['psf', 'mq5']) ? $extension : null;

                    RobotFile::create([
                        'robot_id' => $robot->id,
                        'name' => $fileNames[$index] ?? $file->getClientOriginalName(),
                        'disk' => 'public',
                        'path' => $path,
                        'url' => $fileUrl,
                        'mime_type' => $file->getMimeType(),
                        'file_type' => $fileType,
                        'size_bytes' => $file->getSize(),
                        'sort_order' => $currentMaxSort + $index + 1,
                    ]);
                }
            }

            // Criar nova versão se solicitado
            if ($shouldCreateVersion) {
                // Marcar versão anterior como não atual
                RobotVersion::where('robot_id', $robot->id)
                    ->update(['is_current' => false]);

                RobotVersion::create([
                    'robot_id' => $robot->id,
                    'version' => $robot->version,
                    'code' => $request->code,
                    'changelog' => $request->changelog ?? 'Atualização de código',
                    'is_current' => true,
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();

            $robot->load(['parameters', 'images', 'files', 'user:id,name,email']);

            return response()->json([
                'message' => 'Robô atualizado com sucesso',
                'data' => $robot,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao atualizar robô',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove um robô (soft delete).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = Robot::query();
        if (!$user->is_super_admin) {
            $query->where('user_id', $user->id);
        }

        $robot = $query->findOrFail($id);

        try {
            // Deletar imagens do storage
            foreach ($robot->images as $image) {
                Storage::disk($image->disk)->delete($image->path);
                if ($image->thumbnail_path) {
                    Storage::disk($image->disk)->delete($image->thumbnail_path);
                }
            }

            // Deletar arquivos do storage
            foreach ($robot->files as $file) {
                Storage::disk($file->disk)->delete($file->path);
            }

            $robot->delete(); // Soft delete

            return response()->json([
                'message' => 'Robô deletado com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao deletar robô',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download de arquivo do robô.
     */
    public function downloadFile(Request $request, string $robotId, string $fileId): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $user = $request->user();

        $query = Robot::query();
        if (!$user->is_super_admin) {
            $query->where('user_id', $user->id);
        }

        $robot = $query->findOrFail($robotId);

        $file = RobotFile::where('id', $fileId)
            ->where('robot_id', $robot->id)
            ->firstOrFail();

        $filePath = Storage::disk($file->disk)->path($file->path);

        if (!file_exists($filePath)) {
            return response()->json([
                'message' => 'Arquivo não encontrado no storage',
            ], 404);
        }

        return response()->download(
            $filePath,
            $file->name ?? basename($file->path),
            [
                'Content-Type' => $file->mime_type ?? 'application/octet-stream',
            ]
        );
    }
}
