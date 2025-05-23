<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Mageplaza\Core\Model\Magewire\Concern;

use Magewirephp\Magewire\Model\Element\Redirect as RedirectElement;

trait Redirect
{
    protected $redirect;

    /**
     * Redirect away from the current page.
     */
    public function redirect(string $path, ?array $params = null, ?bool $secure = true): RedirectElement
    {
        return $this->redirect = new RedirectElement($path, $params, $secure);
    }

    public function getRedirect(): ?RedirectElement
    {
        return $this->redirect;
    }
}
