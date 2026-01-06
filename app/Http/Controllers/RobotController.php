<?php

namespace App\Http\Controllers;

use App\Models\Robot;
use App\Models\RobotImage;
use App\Models\RobotParameter;
use App\Models\RobotVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

        $query = Robot::with(['parameters', 'images', 'user:id,name,email'])
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
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'language' => 'required|string|max:20|in:nelogica,python,js,other,meta-traider',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'code' => 'required|string',
            'is_active' => 'nullable|boolean',
            'parameters' => 'nullable|array',
            'parameters.*.key' => 'required|string|max:80',
            'parameters.*.label' => 'required|string|max:120',
            'parameters.*.type' => 'required|string|max:20|in:number,string,boolean,select',
            'parameters.*.value' => 'required',
            'parameters.*.default_value' => 'nullable',
            'parameters.*.required' => 'nullable|boolean',
            'parameters.*.options' => 'nullable|array',
            'parameters.*.validation_rules' => 'nullable|array',
            'parameters.*.group' => 'nullable|string',
            'parameters.*.sort_order' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'image_titles' => 'nullable|array',
            'image_titles.*' => 'nullable|string|max:120',
            'image_captions' => 'nullable|array',
            'image_captions.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors(),
            ], 422);
        }

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
            $robot->load(['parameters', 'images', 'user:id,name,email']);

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

        $query = Robot::with(['parameters', 'images', 'versions', 'user:id,name,email']);

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
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = Robot::query();
        if (!$user->is_super_admin) {
            $query->where('user_id', $user->id);
        }

        $robot = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'language' => 'sometimes|required|string|max:20|in:pascal,python,js,other',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'code' => 'sometimes|required|string',
            'is_active' => 'nullable|boolean',
            'parameters' => 'nullable|array',
            'parameters.*.id' => 'nullable|exists:robot_parameters,id',
            'parameters.*.key' => 'required|string|max:80',
            'parameters.*.label' => 'required|string|max:120',
            'parameters.*.type' => 'required|string|max:20|in:number,string,boolean,select',
            'parameters.*.value' => 'required',
            'parameters.*.default_value' => 'nullable',
            'parameters.*.required' => 'nullable|boolean',
            'parameters.*.options' => 'nullable|array',
            'parameters.*.validation_rules' => 'nullable|array',
            'parameters.*.group' => 'nullable|string',
            'parameters.*.sort_order' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'image_titles' => 'nullable|array',
            'image_titles.*' => 'nullable|string|max:120',
            'image_captions' => 'nullable|array',
            'image_captions.*' => 'nullable|string|max:255',
            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'exists:robot_images,id',
            'create_version' => 'nullable|boolean', // Se true, cria nova versão ao atualizar código
            'changelog' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors(),
            ], 422);
        }

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

            $robot->load(['parameters', 'images', 'user:id,name,email']);

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
}
